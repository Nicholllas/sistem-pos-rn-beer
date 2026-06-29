const cart = [];

const rupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
};

const escapeHtml = (value) => {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
};

const productSearch = document.getElementById("productSearch");
const checkoutForm = document.getElementById("checkoutForm");
const checkoutButton = document.getElementById("checkoutButton");
const paymentHint = document.getElementById("paymentHint");

function getCartTotal() {
    return cart.reduce((sum, item) => sum + item.qty * item.price, 0);
}

function getPayments() {
    const payments = [];

    document.querySelectorAll(".payment-amount").forEach((input) => {
        const amount = parseFloat(input.value) || 0;
        const method = input.dataset.method;

        if (amount > 0) {
            payments.push({
                payment_method: method,
                amount: amount,
            });
        }
    });

    return payments;
}

function getPaymentSummary() {
    const payments = getPayments();

    const totalPaid = payments.reduce((sum, payment) => {
        return sum + payment.amount;
    }, 0);

    const cashPaid = payments
        .filter((payment) => payment.payment_method === "cash")
        .reduce((sum, payment) => sum + payment.amount, 0);

    const nonCashPaid = payments
        .filter((payment) =>
            ["qris", "transfer"].includes(payment.payment_method),
        )
        .reduce((sum, payment) => sum + payment.amount, 0);

    return {
        payments,
        totalPaid,
        cashPaid,
        nonCashPaid,
    };
}

function renderPaymentHiddenInputs() {
    const paymentHiddenInputs = document.getElementById("paymentHiddenInputs");
    const payments = getPayments();

    paymentHiddenInputs.innerHTML = "";

    payments.forEach((payment, index) => {
        paymentHiddenInputs.innerHTML += `
            <input type="hidden" name="payments[${index}][payment_method]" value="${payment.payment_method}">
            <input type="hidden" name="payments[${index}][amount]" value="${payment.amount}">
        `;
    });
}

function calculatePaymentInfo() {
    const total = getCartTotal();
    const { totalPaid, cashPaid, nonCashPaid } = getPaymentSummary();

    const kurang = total - totalPaid;
    const kembali = totalPaid - total;

    document.getElementById("changeText").innerText = rupiah(
        kembali > 0 ? kembali : 0,
    );

    if (total <= 0) {
        paymentHint.className = "text-muted d-block mt-1";
        paymentHint.innerText = "Masukkan nominal pembayaran.";
        renderPaymentHiddenInputs();
        return;
    }

    if (totalPaid <= 0) {
        paymentHint.className = "text-danger d-block mt-1";
        paymentHint.innerText = `Inputan masih kurang ${rupiah(total)} dari total yang harus dibayar pembeli.`;
        renderPaymentHiddenInputs();
        return;
    }

    if (totalPaid < total) {
        paymentHint.className = "text-danger d-block mt-1";
        paymentHint.innerText = `Inputan masih kurang ${rupiah(kurang)} dari total yang harus dibayar pembeli.`;
        renderPaymentHiddenInputs();
        return;
    }

    if (nonCashPaid > total) {
        paymentHint.className = "text-danger d-block mt-1";
        paymentHint.innerText =
            "Total pembayaran QRIS/Transfer tidak boleh melebihi total transaksi.";
        renderPaymentHiddenInputs();
        return;
    }

    if (kembali > 0 && cashPaid <= 0) {
        paymentHint.className = "text-danger d-block mt-1";
        paymentHint.innerText =
            "Kelebihan pembayaran hanya diperbolehkan jika ada pembayaran cash.";
        renderPaymentHiddenInputs();
        return;
    }

    if (kembali > 0) {
        paymentHint.className = "text-success d-block mt-1";
        paymentHint.innerText = `Pembayaran cukup. Kembalian pembeli ${rupiah(kembali)}.`;
        renderPaymentHiddenInputs();
        return;
    }

    paymentHint.className = "text-success d-block mt-1";
    paymentHint.innerText = "Pembayaran sudah sesuai dengan total transaksi.";

    renderPaymentHiddenInputs();
}

document.querySelectorAll(".add-to-cart").forEach((button) => {
    button.addEventListener("click", function () {
        const product = {
            id: parseInt(this.dataset.id),
            name: this.dataset.name,
            price: parseFloat(this.dataset.price),
            stock: parseInt(this.dataset.stock),
            qty: 1,
        };

        const existing = cart.find((item) => item.id === product.id);

        if (existing) {
            if (existing.qty >= existing.stock) {
                alert("Qty tidak boleh melebihi stok tersedia.");
                return;
            }

            existing.qty++;
        } else {
            cart.push(product);
        }

        renderCart();
    });
});

function renderCart() {
    const cartItems = document.getElementById("cartItems");
    const hiddenInputs = document.getElementById("cartHiddenInputs");

    cartItems.innerHTML = "";
    hiddenInputs.innerHTML = "";

    if (cart.length === 0) {
        cartItems.innerHTML = `
            <p class="text-muted text-center mb-0">
                Keranjang masih kosong.
            </p>
        `;
    }

    let grandTotal = 0;

    cart.forEach((item, index) => {
        const subtotal = item.qty * item.price;
        grandTotal += subtotal;

        cartItems.innerHTML += `
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between">
                    <strong>${escapeHtml(item.name)}</strong>

                    <button type="button"
                            class="btn btn-danger btn-sm"
                            data-action="remove"
                            data-id="${item.id}">
                        X
                    </button>
                </div>

                <small>${rupiah(item.price)} x ${item.qty}</small>

                <div class="mt-2 d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-secondary btn-sm"
                            data-action="decrease"
                            data-id="${item.id}">
                        -
                    </button>

                    <span>${item.qty}</span>

                    <button type="button"
                            class="btn btn-secondary btn-sm"
                            data-action="increase"
                            data-id="${item.id}">
                        +
                    </button>

                    <strong class="ms-auto">${rupiah(subtotal)}</strong>
                </div>
            </div>
        `;

        hiddenInputs.innerHTML += `
            <input type="hidden" name="items[${index}][product_id]" value="${item.id}">
            <input type="hidden" name="items[${index}][qty]" value="${item.qty}">
        `;
    });

    document.getElementById("grandTotalText").innerText = rupiah(grandTotal);

    calculatePaymentInfo();
}

document
    .getElementById("cartItems")
    .addEventListener("click", function (event) {
        const button = event.target.closest("button");

        if (!button) {
            return;
        }

        const id = parseInt(button.dataset.id);
        const action = button.dataset.action;

        if (action === "increase") {
            increaseQty(id);
        }

        if (action === "decrease") {
            decreaseQty(id);
        }

        if (action === "remove") {
            removeItem(id);
        }
    });

function increaseQty(id) {
    const item = cart.find((item) => item.id === id);

    if (!item) {
        return;
    }

    if (item.qty >= item.stock) {
        alert("Qty tidak boleh melebihi stok tersedia.");
        return;
    }

    item.qty++;
    renderCart();
}

function decreaseQty(id) {
    const item = cart.find((item) => item.id === id);

    if (!item) {
        return;
    }

    item.qty--;

    if (item.qty <= 0) {
        removeItem(id);
        return;
    }

    renderCart();
}

function removeItem(id) {
    const index = cart.findIndex((item) => item.id === id);

    if (index !== -1) {
        cart.splice(index, 1);
    }

    renderCart();
}

document.querySelectorAll(".payment-amount").forEach((input) => {
    input.addEventListener("input", calculatePaymentInfo);
});

if (checkoutForm) {
    checkoutForm.addEventListener("submit", function (event) {
        if (cart.length === 0) {
            event.preventDefault();
            alert("Keranjang masih kosong.");
            return;
        }

        const total = getCartTotal();
        const { payments, totalPaid, cashPaid, nonCashPaid } =
            getPaymentSummary();

        if (payments.length === 0) {
            event.preventDefault();
            alert("Minimal satu metode pembayaran harus diisi.");
            return;
        }

        if (totalPaid < total) {
            event.preventDefault();
            alert(`Pembayaran masih kurang ${rupiah(total - totalPaid)}.`);
            return;
        }

        if (nonCashPaid > total) {
            event.preventDefault();
            alert(
                "Total pembayaran QRIS/Transfer tidak boleh melebihi total transaksi.",
            );
            return;
        }

        if (totalPaid > total && cashPaid <= 0) {
            event.preventDefault();
            alert(
                "Kelebihan pembayaran hanya diperbolehkan jika ada pembayaran cash.",
            );
            return;
        }

        renderPaymentHiddenInputs();

        const confirmCheckout = confirm(
            `Total transaksi ${rupiah(total)}. Total bayar ${rupiah(totalPaid)}. Lanjut checkout?`,
        );

        if (!confirmCheckout) {
            event.preventDefault();
            return;
        }

        checkoutButton.disabled = true;
        checkoutButton.innerText = "Memproses transaksi...";
    });
}

if (productSearch) {
    productSearch.addEventListener("input", function () {
        const keyword = this.value.toUpperCase();

        document.querySelectorAll(".product-card").forEach((card) => {
            const name = card.dataset.name || "";
            const sku = card.dataset.sku || "";
            const barcode = card.dataset.barcode || "";

            const isMatch =
                name.includes(keyword) ||
                sku.includes(keyword) ||
                barcode.includes(keyword);

            card.style.display = isMatch ? "" : "none";
        });
    });
}
