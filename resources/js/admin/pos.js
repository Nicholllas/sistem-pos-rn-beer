import Swal from "sweetalert2";

const cart = [];

const PAYMENT_LABELS = {
    cash: "Cash",
    qris: "QRIS",
    transfer: "Transfer",
};

const productSearch = document.getElementById("productSearch");
const productSearchEmpty = document.getElementById("productSearchEmpty");
const productCards = [...document.querySelectorAll(".product-card")];

const cartItems = document.getElementById("cartItems");
const cartHiddenInputs = document.getElementById("cartHiddenInputs");
const paymentHiddenInputs = document.getElementById("paymentHiddenInputs");

const grandTotalText = document.getElementById("grandTotalText");
const changeText = document.getElementById("changeText");
const changeSection = changeText?.closest(".pos-change-section");

const checkoutForm = document.getElementById("checkoutForm");
const checkoutButton = document.getElementById("checkoutButton");
const paymentHint = document.getElementById("paymentHint");

const paymentInputs = [...document.querySelectorAll(".payment-amount")];

let isSubmitting = false;

/* =========================================================
   FORMATTER & HELPERS
   ========================================================= */

const rupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(Number(number) || 0);
};

const escapeHtml = (value) => {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
};

const normalizeText = (value) => {
    return String(value ?? "")
        .trim()
        .toLowerCase();
};

const parseAmount = (value) => {
    const amount = Number.parseFloat(value);

    if (!Number.isFinite(amount) || amount < 0) {
        return 0;
    }

    return Math.floor(amount);
};

/* =========================================================
   SWEETALERT HELPERS
   ========================================================= */

function showWarning(title, message) {
    return Swal.fire({
        icon: "warning",
        title,
        text: message,
        confirmButtonText: "Mengerti",
        focusConfirm: true,
        customClass: {
            popup: "pos-alert-popup",
            confirmButton: "btn btn-primary px-4",
        },
        buttonsStyling: false,
    });
}

function showError(title, message) {
    return Swal.fire({
        icon: "error",
        title,
        text: message,
        confirmButtonText: "Tutup",
        focusConfirm: true,
        customClass: {
            popup: "pos-alert-popup",
            confirmButton: "btn btn-danger px-4",
        },
        buttonsStyling: false,
    });
}

/* =========================================================
   CART DATA
   ========================================================= */

function getCartTotal() {
    return cart.reduce((total, item) => {
        return total + item.qty * item.price;
    }, 0);
}

function createProductFromButton(button) {
    return {
        id: Number.parseInt(button.dataset.id, 10),
        name: button.dataset.name ?? "-",
        price: Number.parseFloat(button.dataset.price) || 0,
        stock: Number.parseInt(button.dataset.stock, 10) || 0,
        qty: 1,
    };
}

async function addProductToCart(button) {
    if (!button) {
        return;
    }

    const product = createProductFromButton(button);

    if (!product.id || product.stock <= 0) {
        await showWarning(
            "Stok tidak tersedia",
            "Produk ini tidak memiliki stok yang dapat dijual.",
        );

        return;
    }

    const existingItem = cart.find((item) => {
        return item.id === product.id;
    });

    if (existingItem) {
        if (existingItem.qty >= existingItem.stock) {
            await showWarning(
                "Stok tidak mencukupi",
                `Jumlah ${product.name} tidak boleh melebihi stok tersedia sebanyak ${existingItem.stock}.`,
            );

            return;
        }

        existingItem.qty++;
    } else {
        cart.push(product);
    }

    renderCart();
}

/* =========================================================
   PAYMENT DATA
   ========================================================= */

function getPayments() {
    return paymentInputs
        .map((input) => {
            return {
                payment_method: input.dataset.method,
                amount: parseAmount(input.value),
            };
        })
        .filter((payment) => {
            return payment.amount > 0;
        });
}

function getPaymentSummary() {
    const payments = getPayments();

    const totalPaid = payments.reduce((total, payment) => {
        return total + payment.amount;
    }, 0);

    const cashPaid = payments
        .filter((payment) => {
            return payment.payment_method === "cash";
        })
        .reduce((total, payment) => {
            return total + payment.amount;
        }, 0);

    const nonCashPaid = payments
        .filter((payment) => {
            return ["qris", "transfer"].includes(payment.payment_method);
        })
        .reduce((total, payment) => {
            return total + payment.amount;
        }, 0);

    return {
        payments,
        totalPaid,
        cashPaid,
        nonCashPaid,
    };
}

function resetPayments() {
    paymentInputs.forEach((input) => {
        input.value = 0;
    });
}

function renderPaymentHiddenInputs() {
    if (!paymentHiddenInputs) {
        return;
    }

    const payments = getPayments();

    paymentHiddenInputs.innerHTML = payments
        .map((payment, index) => {
            return `
                <input
                    type="hidden"
                    name="payments[${index}][payment_method]"
                    value="${escapeHtml(payment.payment_method)}"
                >

                <input
                    type="hidden"
                    name="payments[${index}][amount]"
                    value="${payment.amount}"
                >
            `;
        })
        .join("");
}

/* =========================================================
   PAYMENT UI
   ========================================================= */

function setPaymentHint(status, message) {
    if (!paymentHint) {
        return;
    }

    paymentHint.className = `pos-payment-hint is-${status}`;
    paymentHint.textContent = message;
}

function updatePaymentMethodBoxes() {
    paymentInputs.forEach((input) => {
        const paymentBox = input.closest(".payment-method-box");
        const amount = parseAmount(input.value);

        paymentBox?.classList.toggle("is-active", amount > 0);
    });
}

function updateCheckoutButton(isValid) {
    if (!checkoutButton || isSubmitting) {
        return;
    }

    checkoutButton.disabled = !isValid;

    if (cart.length === 0) {
        checkoutButton.textContent = "Keranjang Kosong";
        return;
    }

    if (!isValid) {
        checkoutButton.textContent = "Lengkapi Pembayaran";
        return;
    }

    checkoutButton.textContent = "Proses Checkout";
}

function calculatePaymentInfo() {
    const total = getCartTotal();

    const { totalPaid, cashPaid, nonCashPaid } = getPaymentSummary();

    const shortage = Math.max(total - totalPaid, 0);
    const change = Math.max(totalPaid - total, 0);

    if (changeText) {
        changeText.textContent = rupiah(change);
    }

    changeSection?.classList.toggle("has-change", change > 0);

    updatePaymentMethodBoxes();
    renderPaymentHiddenInputs();

    if (total <= 0) {
        setPaymentHint(
            "neutral",
            "Tambahkan produk ke keranjang terlebih dahulu.",
        );

        updateCheckoutButton(false);

        return false;
    }

    if (totalPaid <= 0) {
        setPaymentHint(
            "danger",
            `Masukkan pembayaran sebesar ${rupiah(total)}.`,
        );

        updateCheckoutButton(false);

        return false;
    }

    if (nonCashPaid > total) {
        setPaymentHint(
            "danger",
            "Total pembayaran QRIS dan transfer tidak boleh melebihi total transaksi.",
        );

        updateCheckoutButton(false);

        return false;
    }

    if (totalPaid < total) {
        setPaymentHint(
            "danger",
            `Pembayaran masih kurang ${rupiah(shortage)}.`,
        );

        updateCheckoutButton(false);

        return false;
    }

    if (change > 0 && cashPaid <= 0) {
        setPaymentHint(
            "danger",
            "Kelebihan pembayaran hanya diperbolehkan jika terdapat pembayaran cash.",
        );

        updateCheckoutButton(false);

        return false;
    }

    if (change > 0) {
        setPaymentHint(
            "success",
            `Pembayaran cukup. Kembalian pelanggan ${rupiah(change)}.`,
        );

        updateCheckoutButton(true);

        return true;
    }

    setPaymentHint(
        "success",
        "Pembayaran sudah sesuai dengan total transaksi.",
    );

    updateCheckoutButton(true);

    return true;
}

/* =========================================================
   CART RENDER
   ========================================================= */

function renderCart() {
    if (!cartItems || !cartHiddenInputs) {
        return;
    }

    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="pos-cart-empty">
                <div class="pos-cart-empty-title">
                    Keranjang masih kosong
                </div>

                <p>
                    Pilih produk dari daftar untuk memulai transaksi.
                </p>
            </div>
        `;

        cartHiddenInputs.innerHTML = "";

        if (grandTotalText) {
            grandTotalText.textContent = rupiah(0);
        }

        resetPayments();
        calculatePaymentInfo();

        return;
    }

    cartItems.innerHTML = cart
        .map((item) => {
            const subtotal = item.qty * item.price;

            return `
                <div class="pos-cart-item">
                    <div class="pos-cart-item-header">
                        <div class="pos-cart-item-info">
                            <strong>
                                ${escapeHtml(item.name)}
                            </strong>

                            <small>
                                ${rupiah(item.price)} per item
                            </small>
                        </div>

                        <button
                            type="button"
                            class="pos-cart-remove"
                            data-action="remove"
                            data-id="${item.id}"
                            aria-label="Hapus ${escapeHtml(item.name)}"
                            title="Hapus produk"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="pos-cart-item-footer">
                        <div class="pos-quantity-control">
                            <button
                                type="button"
                                class="pos-quantity-button"
                                data-action="decrease"
                                data-id="${item.id}"
                                aria-label="Kurangi jumlah"
                            >
                                &minus;
                            </button>

                            <span class="pos-quantity-value">
                                ${item.qty}
                            </span>

                            <button
                                type="button"
                                class="pos-quantity-button"
                                data-action="increase"
                                data-id="${item.id}"
                                aria-label="Tambah jumlah"
                            >
                                &plus;
                            </button>
                        </div>

                        <div class="pos-cart-subtotal">
                            <small>Subtotal</small>

                            <strong>
                                ${rupiah(subtotal)}
                            </strong>
                        </div>
                    </div>
                </div>
            `;
        })
        .join("");

    cartHiddenInputs.innerHTML = cart
        .map((item, index) => {
            return `
                <input
                    type="hidden"
                    name="items[${index}][product_id]"
                    value="${item.id}"
                >

                <input
                    type="hidden"
                    name="items[${index}][qty]"
                    value="${item.qty}"
                >
            `;
        })
        .join("");

    if (grandTotalText) {
        grandTotalText.textContent = rupiah(getCartTotal());
    }

    calculatePaymentInfo();
}

/* =========================================================
   CART ACTIONS
   ========================================================= */

async function increaseQty(id) {
    const item = cart.find((cartItem) => {
        return cartItem.id === id;
    });

    if (!item) {
        return;
    }

    if (item.qty >= item.stock) {
        await showWarning(
            "Stok tidak mencukupi",
            `Jumlah ${item.name} tidak boleh melebihi stok tersedia sebanyak ${item.stock}.`,
        );

        return;
    }

    item.qty++;
    renderCart();
}

function decreaseQty(id) {
    const item = cart.find((cartItem) => {
        return cartItem.id === id;
    });

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
    const index = cart.findIndex((item) => {
        return item.id === id;
    });

    if (index === -1) {
        return;
    }

    cart.splice(index, 1);
    renderCart();
}

/* =========================================================
   PRODUCT SEARCH
   ========================================================= */

function filterProducts() {
    if (!productSearch) {
        return [];
    }

    const keyword = normalizeText(productSearch.value);
    const visibleCards = [];

    productCards.forEach((card) => {
        const name = normalizeText(card.dataset.name);
        const sku = normalizeText(card.dataset.sku);
        const barcode = normalizeText(card.dataset.barcode);

        const isMatch =
            keyword === "" ||
            name.includes(keyword) ||
            sku.includes(keyword) ||
            barcode.includes(keyword);

        card.classList.toggle("d-none", !isMatch);

        if (isMatch) {
            visibleCards.push(card);
        }
    });

    productSearchEmpty?.classList.toggle(
        "d-none",
        keyword === "" || visibleCards.length > 0,
    );

    return visibleCards;
}

function resetProductSearch() {
    if (!productSearch) {
        return;
    }

    productSearch.value = "";
    filterProducts();
    productSearch.focus();
}

async function addProductFromSearch() {
    if (!productSearch) {
        return;
    }

    const keyword = normalizeText(productSearch.value);

    if (keyword === "") {
        return;
    }

    const exactMatch = productCards.find((card) => {
        const sku = normalizeText(card.dataset.sku);
        const barcode = normalizeText(card.dataset.barcode);

        return sku === keyword || barcode === keyword;
    });

    const visibleCards = filterProducts();

    const targetCard =
        exactMatch ?? (visibleCards.length === 1 ? visibleCards[0] : null);

    if (!targetCard) {
        await showWarning(
            "Produk belum dipilih",
            "Gunakan SKU atau barcode yang tepat, atau pilih produk dari hasil pencarian.",
        );

        return;
    }

    const button = targetCard.querySelector(".add-to-cart");

    await addProductToCart(button);
    resetProductSearch();
}

/* =========================================================
   EVENT LISTENERS
   ========================================================= */

document.querySelectorAll(".add-to-cart").forEach((button) => {
    button.addEventListener("click", async function () {
        await addProductToCart(this);
    });
});

cartItems?.addEventListener("click", async function (event) {
    const button = event.target.closest("[data-action]");

    if (!button || !cartItems.contains(button)) {
        return;
    }

    const id = Number.parseInt(button.dataset.id, 10);
    const action = button.dataset.action;

    if (!id) {
        return;
    }

    switch (action) {
        case "increase":
            await increaseQty(id);
            break;

        case "decrease":
            decreaseQty(id);
            break;

        case "remove":
            removeItem(id);
            break;
    }
});

paymentInputs.forEach((input) => {
    input.addEventListener("focus", function () {
        if (parseAmount(this.value) === 0) {
            this.select();
        }
    });

    input.addEventListener("input", function () {
        if (Number(this.value) < 0) {
            this.value = 0;
        }

        calculatePaymentInfo();
    });

    input.addEventListener("blur", function () {
        if (this.value === "") {
            this.value = 0;
        }

        calculatePaymentInfo();
    });
});

productSearch?.addEventListener("input", filterProducts);

productSearch?.addEventListener("keydown", async function (event) {
    if (event.key !== "Enter") {
        return;
    }

    event.preventDefault();

    await addProductFromSearch();
});

/* =========================================================
   CHECKOUT
   ========================================================= */

checkoutForm?.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (isSubmitting) {
        return;
    }

    if (cart.length === 0) {
        await showWarning(
            "Keranjang masih kosong",
            "Tambahkan minimal satu produk sebelum melakukan checkout.",
        );

        productSearch?.focus();

        return;
    }

    const total = getCartTotal();

    const { payments, totalPaid, cashPaid, nonCashPaid } = getPaymentSummary();

    if (payments.length === 0) {
        await showWarning(
            "Pembayaran belum diisi",
            "Minimal satu metode pembayaran harus diisi.",
        );

        paymentInputs[0]?.focus();

        return;
    }

    if (nonCashPaid > total) {
        await showError(
            "Pembayaran tidak valid",
            "Total pembayaran QRIS dan transfer tidak boleh melebihi total transaksi.",
        );

        return;
    }

    if (totalPaid < total) {
        await showWarning(
            "Pembayaran belum cukup",
            `Pembayaran masih kurang ${rupiah(total - totalPaid)}.`,
        );

        return;
    }

    if (totalPaid > total && cashPaid <= 0) {
        await showError(
            "Kelebihan pembayaran tidak valid",
            "Kelebihan pembayaran hanya diperbolehkan jika terdapat pembayaran cash.",
        );

        return;
    }

    renderPaymentHiddenInputs();

    const paymentDetailsHtml = payments
        .map((payment) => {
            const label =
                PAYMENT_LABELS[payment.payment_method] ??
                payment.payment_method;

            return `
                <div class="pos-confirmation-row pos-confirmation-method">
                    <span>${escapeHtml(label)}</span>

                    <strong>
                        ${rupiah(payment.amount)}
                    </strong>
                </div>
            `;
        })
        .join("");

    const change = Math.max(totalPaid - total, 0);

    const result = await Swal.fire({
        icon: "question",
        title: "Konfirmasi Checkout",
        html: `
            <div class="pos-confirmation">
                <div class="pos-confirmation-row pos-confirmation-total">
                    <span>Total Belanja</span>

                    <strong>
                        ${rupiah(total)}
                    </strong>
                </div>

                <div class="pos-confirmation-divider"></div>

                <div class="pos-confirmation-label">
                    Rincian Pembayaran
                </div>

                ${paymentDetailsHtml}

                <div class="pos-confirmation-divider"></div>

                <div class="pos-confirmation-row pos-confirmation-paid">
                    <span>Total Pembayaran</span>

                    <strong>
                        ${rupiah(totalPaid)}
                    </strong>
                </div>

                <div class="pos-confirmation-row pos-confirmation-change ${change === 0 ? "is-zero" : ""}">
                    <span>Kembalian</span>

                    <strong>
                        ${rupiah(change)}
                    </strong>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "Ya, Proses Checkout",
        cancelButtonText: "Periksa Kembali",
        reverseButtons: true,
        focusCancel: true,
        allowOutsideClick: false,
        customClass: {
            popup: "pos-alert-popup",
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-outline-secondary",
        },
        buttonsStyling: false,
    });

    if (!result.isConfirmed) {
        return;
    }

    isSubmitting = true;

    if (checkoutButton) {
        checkoutButton.disabled = true;
        checkoutButton.textContent = "Memproses transaksi...";
    }

    checkoutForm.submit();
});

/* =========================================================
   INITIAL STATE
   ========================================================= */

filterProducts();
renderCart();
productSearch?.focus();
