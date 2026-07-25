import Swal from "sweetalert2";

/* =========================================================
   DOM ELEMENTS
   ========================================================= */

const purchaseForm = document.getElementById("purchaseForm");

const purchaseItemsBody = document.getElementById("purchaseItemsBody");

const addPurchaseItemButton = document.getElementById("addPurchaseItemButton");

const purchaseSubmitButton = document.getElementById("purchaseSubmitButton");

const grandTotalText = document.getElementById("grandTotalText");

const purchaseItemCount = document.getElementById("purchaseItemCount");

const purchaseQtyTotal = document.getElementById("purchaseQtyTotal");

const purchaseGrandTotalSummary = document.getElementById(
    "purchaseGrandTotalSummary",
);

const purchaseSupplierSelect = document.getElementById("supplier_id");

const purchaseFinanceAccountSelect =
    document.getElementById("finance_account_id");

const purchaseDateInput = document.getElementById("purchase_date");

/* Finance account preview */

const purchaseFinanceReference = document.getElementById(
    "purchaseFinanceReference",
);

const purchaseFinanceAccountName = document.getElementById(
    "purchaseFinanceAccountName",
);

const purchaseFinanceAccountCode = document.getElementById(
    "purchaseFinanceAccountCode",
);

const purchaseFinanceCurrentBalance = document.getElementById(
    "purchaseFinanceCurrentBalance",
);

const purchaseFinancePurchaseTotal = document.getElementById(
    "purchaseFinancePurchaseTotal",
);

const purchaseFinanceRemainingBalance = document.getElementById(
    "purchaseFinanceRemainingBalance",
);

const purchaseFinanceBalanceMessage = document.getElementById(
    "purchaseFinanceBalanceMessage",
);

/* =========================================================
   STATE
   ========================================================= */

let purchaseProducts = [];
let oldPurchaseItems = [];
let purchaseItemIndex = 0;
let isPurchaseSubmitting = false;

/* =========================================================
   GENERAL HELPERS
   ========================================================= */

function parseJsonElement(elementId) {
    const element = document.getElementById(elementId);

    if (!element) {
        return [];
    }

    try {
        return JSON.parse(element.textContent || "[]");
    } catch (error) {
        console.error(`Gagal membaca data dari ${elementId}:`, error);

        return [];
    }
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatRupiah(value) {
    const number = Number(value);

    const safeValue = Number.isFinite(number) ? number : 0;

    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(safeValue);
}

function parseInteger(value) {
    const number = Number.parseInt(value, 10);

    return Number.isFinite(number) ? number : 0;
}

function parseAmount(value) {
    const number = Number.parseFloat(value);

    return Number.isFinite(number) ? number : 0;
}

function updatePurchaseSelectTitle(selectElement) {
    if (!selectElement) {
        return;
    }

    const selectedOption = selectElement.options[selectElement.selectedIndex];

    if (!selectedOption || !selectElement.value) {
        selectElement.removeAttribute("title");

        return;
    }

    selectElement.title =
        selectedOption.dataset.fullLabel || selectedOption.textContent.trim();
}

/* =========================================================
   SWEETALERT HELPER
   ========================================================= */

async function showPurchaseAlert({
    icon = "warning",
    title,
    text,
    focusElement = null,
}) {
    await Swal.fire({
        icon,
        title,
        text,
        confirmButtonText: "Mengerti",
        customClass: {
            popup: "crud-alert-popup",
            confirmButton: "btn btn-primary px-4",
        },
        buttonsStyling: false,
    });

    focusElement?.focus();
}

/* =========================================================
   PRODUCT OPTIONS
   ========================================================= */

function buildProductOptions(selectedProductId = "") {
    let options = `
        <option value="">
            Pilih produk
        </option>
    `;

    purchaseProducts.forEach((product) => {
        const selected =
            String(product.id) === String(selectedProductId) ? "selected" : "";

        const productName = String(product.name || "Produk");

        const productStock = Math.max(parseInteger(product.stock), 0);

        /*
         * Tampilan pilihan produk:
         * Nama produk + stok tersisa.
         */
        const productLabel = `${productName} · Stok tersisa ${productStock}`;

        options += `
            <option
                value="${escapeHtml(product.id)}"
                data-cost-price="${escapeHtml(product.cost_price ?? 0)}"
                data-stock="${productStock}"
                data-full-label="${escapeHtml(productLabel)}"
                ${selected}
            >
                ${escapeHtml(productLabel)}
            </option>
        `;
    });

    return options;
}

/* =========================================================
   PURCHASE ITEM ROW
   ========================================================= */

function createPurchaseRow(item = {}) {
    const rowIndex = purchaseItemIndex++;

    const productId = item.product_id ?? "";
    const quantity = item.qty ?? 1;
    const costPrice = item.cost_price ?? "";

    const row = document.createElement("div");

    row.className = "purchase-item-row";

    row.innerHTML = `
        <div class="purchase-item-field purchase-item-product">
            <span class="purchase-item-mobile-label">
                Produk
            </span>

            <select
                name="items[${rowIndex}][product_id]"
                class="form-select crud-form-control purchase-product-select"
                required
            >
                ${buildProductOptions(productId)}
            </select>
        </div>

        <div class="purchase-item-field purchase-item-quantity">
            <span class="purchase-item-mobile-label">
                Qty
            </span>

            <input
                type="number"
                name="items[${rowIndex}][qty]"
                class="form-control crud-form-control purchase-qty-input"
                min="1"
                step="1"
                value="${escapeHtml(quantity)}"
                required
            >
        </div>

        <div class="purchase-item-field purchase-item-cost">
            <span class="purchase-item-mobile-label">
                Harga Modal
            </span>

            <div class="purchase-cost-input-group">
                <span class="purchase-cost-prefix">
                    Rp
                </span>

                <input
                    type="number"
                    name="items[${rowIndex}][cost_price]"
                    class="form-control purchase-cost-input"
                    min="0"
                    step="1"
                    value="${escapeHtml(costPrice)}"
                    placeholder="0"
                    required
                >
            </div>
        </div>

        <div class="purchase-item-field purchase-item-subtotal-wrapper">
            <span class="purchase-item-mobile-label">
                Subtotal
            </span>

            <strong class="purchase-subtotal">
                Rp 0
            </strong>
        </div>

        <div class="purchase-item-action">
            <button
                type="button"
                class="purchase-remove-button"
                aria-label="Hapus produk"
                title="Hapus produk"
            >
                &times;
            </button>
        </div>
    `;

    purchaseItemsBody?.appendChild(row);

    const productSelect = row.querySelector(".purchase-product-select");

    updatePurchaseSelectTitle(productSelect);
    updatePurchaseRow(row);
    updatePurchaseSummary();

    return row;
}

function getPurchaseRows() {
    if (!purchaseItemsBody) {
        return [];
    }

    return [...purchaseItemsBody.querySelectorAll(".purchase-item-row")];
}

function getRowValues(row) {
    const productSelect = row.querySelector(".purchase-product-select");

    const quantityInput = row.querySelector(".purchase-qty-input");

    const costPriceInput = row.querySelector(".purchase-cost-input");

    return {
        productSelect,
        quantityInput,
        costPriceInput,

        productId: productSelect?.value || "",

        quantity: parseInteger(quantityInput?.value),

        costPrice: parseAmount(costPriceInput?.value),
    };
}

function updatePurchaseRow(row) {
    const { quantity, costPrice } = getRowValues(row);

    const safeQuantity = Math.max(quantity, 0);

    const safeCostPrice = Math.max(costPrice, 0);

    const subtotal = safeQuantity * safeCostPrice;

    const subtotalElement = row.querySelector(".purchase-subtotal");

    if (subtotalElement) {
        subtotalElement.textContent = formatRupiah(subtotal);
    }
}

/* =========================================================
   PURCHASE SUMMARY
   ========================================================= */

function getPurchaseSummary() {
    let itemCount = 0;
    let totalQuantity = 0;
    let grandTotal = 0;

    getPurchaseRows().forEach((row) => {
        const { productId, quantity, costPrice } = getRowValues(row);

        /*
         * Baris kosong tidak ikut dihitung.
         */
        if (!productId) {
            return;
        }

        const safeQuantity = Math.max(quantity, 0);

        const safeCostPrice = Math.max(costPrice, 0);

        itemCount++;
        totalQuantity += safeQuantity;
        grandTotal += safeQuantity * safeCostPrice;
    });

    return {
        itemCount,
        totalQuantity,
        grandTotal,
    };
}

function updatePurchaseSummary() {
    const { itemCount, totalQuantity, grandTotal } = getPurchaseSummary();

    if (purchaseItemCount) {
        purchaseItemCount.textContent = itemCount.toLocaleString("id-ID");
    }

    if (purchaseQtyTotal) {
        purchaseQtyTotal.textContent = totalQuantity.toLocaleString("id-ID");
    }

    if (purchaseGrandTotalSummary) {
        purchaseGrandTotalSummary.textContent = formatRupiah(grandTotal);
    }

    if (grandTotalText) {
        grandTotalText.textContent = formatRupiah(grandTotal);
    }

    updatePurchaseFinancePreview();
}

/* =========================================================
   DUPLICATE PRODUCT
   ========================================================= */

function getDuplicateProductIds() {
    const selectedProductIds = getPurchaseRows()
        .map((row) => {
            return getRowValues(row).productId;
        })
        .filter(Boolean);

    const seen = new Set();
    const duplicates = new Set();

    selectedProductIds.forEach((productId) => {
        if (seen.has(productId)) {
            duplicates.add(productId);
        }

        seen.add(productId);
    });

    return [...duplicates];
}

/* =========================================================
   FINANCE ACCOUNT
   ========================================================= */

function getSelectedFinanceAccount() {
    if (!purchaseFinanceAccountSelect?.value) {
        return null;
    }

    const selectedOption =
        purchaseFinanceAccountSelect.options[
            purchaseFinanceAccountSelect.selectedIndex
        ];

    if (!selectedOption) {
        return null;
    }

    return {
        id: purchaseFinanceAccountSelect.value,

        code: selectedOption.dataset.code || "-",

        name: selectedOption.dataset.name || "-",

        balance: Number(selectedOption.dataset.balance) || 0,
    };
}

function setPurchaseFinanceState(element, state) {
    if (!element) {
        return;
    }

    element.classList.remove("is-success", "is-danger", "is-neutral");

    element.classList.add(state);
}

function updatePurchaseFinancePreview() {
    const account = getSelectedFinanceAccount();

    const { grandTotal } = getPurchaseSummary();

    if (purchaseFinanceReference) {
        purchaseFinanceReference.hidden = !account;
    }

    if (!account) {
        return;
    }

    const remainingBalance = account.balance - grandTotal;

    if (purchaseFinanceAccountName) {
        purchaseFinanceAccountName.textContent = account.name;
    }

    if (purchaseFinanceAccountCode) {
        purchaseFinanceAccountCode.textContent = account.code;
    }

    if (purchaseFinanceCurrentBalance) {
        purchaseFinanceCurrentBalance.textContent = formatRupiah(
            account.balance,
        );
    }

    if (purchaseFinancePurchaseTotal) {
        purchaseFinancePurchaseTotal.textContent = formatRupiah(grandTotal);
    }

    if (purchaseFinanceRemainingBalance) {
        purchaseFinanceRemainingBalance.textContent =
            formatRupiah(remainingBalance);

        setPurchaseFinanceState(
            purchaseFinanceRemainingBalance,
            remainingBalance < 0 ? "is-danger" : "is-success",
        );
    }

    if (!purchaseFinanceBalanceMessage) {
        return;
    }

    if (grandTotal <= 0) {
        purchaseFinanceBalanceMessage.textContent =
            "Tambahkan produk untuk melihat estimasi saldo setelah pembelian.";

        setPurchaseFinanceState(purchaseFinanceBalanceMessage, "is-neutral");

        return;
    }

    if (remainingBalance < 0) {
        purchaseFinanceBalanceMessage.textContent = `Saldo akun kurang ${formatRupiah(
            Math.abs(remainingBalance),
        )} untuk membayar pembelian ini.`;

        setPurchaseFinanceState(purchaseFinanceBalanceMessage, "is-danger");

        return;
    }

    purchaseFinanceBalanceMessage.textContent = `Saldo mencukupi. Estimasi sisa saldo ${formatRupiah(
        remainingBalance,
    )}.`;

    setPurchaseFinanceState(purchaseFinanceBalanceMessage, "is-success");
}

/* =========================================================
   FORM VALIDATION
   ========================================================= */

async function validatePurchaseForm() {
    if (!purchaseSupplierSelect?.value) {
        await showPurchaseAlert({
            title: "Supplier belum dipilih",
            text: "Pilih supplier untuk transaksi pembelian ini.",
            focusElement: purchaseSupplierSelect,
        });

        return false;
    }

    if (!purchaseFinanceAccountSelect?.value) {
        await showPurchaseAlert({
            title: "Akun pembayaran belum dipilih",
            text: "Pilih akun yang digunakan untuk membayar pembelian ini.",
            focusElement: purchaseFinanceAccountSelect,
        });

        return false;
    }

    if (!purchaseDateInput?.value) {
        await showPurchaseAlert({
            title: "Tanggal pembelian belum diisi",
            text: "Masukkan tanggal transaksi pembelian.",
            focusElement: purchaseDateInput,
        });

        return false;
    }

    const rows = getPurchaseRows();

    const selectedRows = rows.filter((row) => {
        return Boolean(getRowValues(row).productId);
    });

    if (selectedRows.length === 0) {
        await showPurchaseAlert({
            title: "Produk belum ditambahkan",
            text: "Pilih minimal satu produk untuk transaksi pembelian.",
            focusElement: rows[0]?.querySelector(".purchase-product-select"),
        });

        return false;
    }

    /*
     * Semua baris harus lengkap.
     * Baris tambahan yang masih kosong juga dianggap belum valid.
     */
    for (const row of rows) {
        const {
            productSelect,
            quantityInput,
            costPriceInput,
            productId,
            quantity,
            costPrice,
        } = getRowValues(row);

        if (!productId) {
            await showPurchaseAlert({
                title: "Produk belum dipilih",
                text: "Pilih produk atau hapus baris yang tidak digunakan.",
                focusElement: productSelect,
            });

            return false;
        }

        if (quantityInput?.value === "" || quantity < 1) {
            await showPurchaseAlert({
                title: "Qty tidak valid",
                text: "Jumlah pembelian minimal 1 unit.",
                focusElement: quantityInput,
            });

            return false;
        }

        if (costPriceInput?.value === "" || costPrice < 0) {
            await showPurchaseAlert({
                title: "Harga modal tidak valid",
                text: "Harga modal wajib diisi dan tidak boleh negatif.",
                focusElement: costPriceInput,
            });

            return false;
        }
    }

    if (getDuplicateProductIds().length > 0) {
        await showPurchaseAlert({
            title: "Produk tercatat lebih dari sekali",
            text: "Gabungkan produk yang sama menjadi satu baris dan sesuaikan qty-nya.",
        });

        return false;
    }

    const financeAccount = getSelectedFinanceAccount();

    const { grandTotal } = getPurchaseSummary();

    if (financeAccount && grandTotal > financeAccount.balance) {
        const shortage = grandTotal - financeAccount.balance;

        await Swal.fire({
            icon: "error",
            title: "Saldo akun tidak mencukupi",
            html: `
                <div class="pos-confirmation">
                    <div class="pos-confirmation-row">
                        <span>Saldo tersedia</span>

                        <strong>
                            ${formatRupiah(financeAccount.balance)}
                        </strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Total pembelian</span>

                        <strong>
                            ${formatRupiah(grandTotal)}
                        </strong>
                    </div>

                    <div class="pos-confirmation-divider"></div>

                    <div class="pos-confirmation-row">
                        <span>Kekurangan saldo</span>

                        <strong>
                            ${formatRupiah(shortage)}
                        </strong>
                    </div>
                </div>
            `,
            confirmButtonText: "Periksa Kembali",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        purchaseFinanceAccountSelect?.focus();

        return false;
    }

    return true;
}

/* =========================================================
   ADD PRODUCT
   ========================================================= */

addPurchaseItemButton?.addEventListener("click", function () {
    const newRow = createPurchaseRow();

    newRow.querySelector(".purchase-product-select")?.focus();
});

/* =========================================================
   PRODUCT CHANGE
   ========================================================= */

purchaseItemsBody?.addEventListener("change", async function (event) {
    const productSelect = event.target.closest(".purchase-product-select");

    if (!productSelect) {
        return;
    }

    const row = productSelect.closest(".purchase-item-row");

    if (!row) {
        return;
    }

    const duplicateIds = getDuplicateProductIds();

    if (productSelect.value && duplicateIds.includes(productSelect.value)) {
        const selectedOption =
            productSelect.options[productSelect.selectedIndex];

        const productName = selectedOption?.textContent?.trim() || "Produk";

        productSelect.value = "";

        const costPriceInput = row.querySelector(".purchase-cost-input");

        if (costPriceInput) {
            costPriceInput.value = "";
        }

        updatePurchaseSelectTitle(productSelect);

        updatePurchaseRow(row);
        updatePurchaseSummary();

        await showPurchaseAlert({
            title: "Produk sudah ditambahkan",
            text: `${productName} sudah ada di daftar pembelian.`,
            focusElement: productSelect,
        });

        return;
    }

    updatePurchaseSelectTitle(productSelect);

    const costPriceInput = row.querySelector(".purchase-cost-input");

    const selectedOption = productSelect.options[productSelect.selectedIndex];

    if (!productSelect.value) {
        if (costPriceInput) {
            costPriceInput.value = "";
        }

        updatePurchaseRow(row);
        updatePurchaseSummary();

        return;
    }

    const defaultCostPrice = parseAmount(selectedOption?.dataset.costPrice);

    /*
     * Saat produk diganti, harga modal awal
     * mengikuti cost price produk terpilih.
     * User masih dapat mengubahnya manual.
     */
    if (costPriceInput) {
        costPriceInput.value =
            defaultCostPrice > 0 ? Math.round(defaultCostPrice) : 0;
    }

    updatePurchaseRow(row);
    updatePurchaseSummary();
});

/* =========================================================
   QTY AND COST INPUT
   ========================================================= */

purchaseItemsBody?.addEventListener("input", function (event) {
    if (!event.target.matches(".purchase-qty-input, .purchase-cost-input")) {
        return;
    }

    const row = event.target.closest(".purchase-item-row");

    if (!row) {
        return;
    }

    updatePurchaseRow(row);
    updatePurchaseSummary();
});

/* =========================================================
   REMOVE PRODUCT
   ========================================================= */

purchaseItemsBody?.addEventListener("click", function (event) {
    const removeButton = event.target.closest(".purchase-remove-button");

    if (!removeButton) {
        return;
    }

    const row = removeButton.closest(".purchase-item-row");

    row?.remove();

    /*
     * Form selalu menyisakan minimal
     * satu baris produk.
     */
    if (getPurchaseRows().length === 0) {
        createPurchaseRow();
    }

    updatePurchaseSummary();
});

/* =========================================================
   FINANCE ACCOUNT CHANGE
   ========================================================= */

purchaseFinanceAccountSelect?.addEventListener("change", function () {
    updatePurchaseSelectTitle(this);
    updatePurchaseFinancePreview();
});

/* =========================================================
   FORM SUBMIT
   ========================================================= */

purchaseForm?.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (isPurchaseSubmitting) {
        return;
    }

    const isValid = await validatePurchaseForm();

    if (!isValid) {
        return;
    }

    const { itemCount, totalQuantity, grandTotal } = getPurchaseSummary();

    const supplierName =
        purchaseSupplierSelect?.options[
            purchaseSupplierSelect.selectedIndex
        ]?.textContent?.trim() || "-";

    const financeAccount = getSelectedFinanceAccount();

    const financeAccountLabel = financeAccount
        ? `${financeAccount.code} · ${financeAccount.name}`
        : "-";

    const remainingBalance = financeAccount
        ? financeAccount.balance - grandTotal
        : 0;

    const result = await Swal.fire({
        icon: "question",
        title: "Simpan pembelian stok?",
        html: `
                <div class="pos-confirmation">
                    <div class="pos-confirmation-row">
                        <span>Supplier</span>

                        <strong>
                            ${escapeHtml(supplierName)}
                        </strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Akun pembayaran</span>

                        <strong>
                            ${escapeHtml(financeAccountLabel)}
                        </strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Jenis produk</span>

                        <strong>
                            ${itemCount.toLocaleString("id-ID")}
                        </strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Total qty</span>

                        <strong>
                            ${totalQuantity.toLocaleString("id-ID")}
                        </strong>
                    </div>

                    <div class="pos-confirmation-divider"></div>

                    <div class="pos-confirmation-row">
                        <span>Total pembelian</span>

                        <strong>
                            ${formatRupiah(grandTotal)}
                        </strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Estimasi sisa saldo</span>

                        <strong>
                            ${formatRupiah(remainingBalance)}
                        </strong>
                    </div>
                </div>
            `,
        showCancelButton: true,
        confirmButtonText: "Ya, Simpan Pembelian",
        cancelButtonText: "Periksa Kembali",
        reverseButtons: true,
        focusCancel: true,
        allowOutsideClick: false,
        customClass: {
            popup: "crud-alert-popup",
            confirmButton: "btn btn-success px-4",
            cancelButton: "btn btn-outline-secondary px-4",
        },
        buttonsStyling: false,
    });

    if (!result.isConfirmed) {
        return;
    }

    isPurchaseSubmitting = true;

    if (purchaseSubmitButton) {
        purchaseSubmitButton.disabled = true;

        purchaseSubmitButton.textContent = "Menyimpan pembelian...";
    }

    /*
     * Native submit tidak memicu kembali
     * event listener submit di atas.
     */
    purchaseForm.submit();
});

/* =========================================================
   INITIAL PURCHASE DATA
   ========================================================= */

if (purchaseItemsBody) {
    purchaseProducts = parseJsonElement("purchaseProductsData");

    oldPurchaseItems = parseJsonElement("purchaseOldItemsData");

    if (Array.isArray(oldPurchaseItems) && oldPurchaseItems.length > 0) {
        oldPurchaseItems.forEach((item) => {
            createPurchaseRow(item);
        });
    } else {
        createPurchaseRow();
    }

    updatePurchaseSummary();
}

/* =========================================================
   INITIAL FINANCE PREVIEW
   ========================================================= */

updatePurchaseSelectTitle(purchaseFinanceAccountSelect);

updatePurchaseFinancePreview();

/* =========================================================
   AUTO OPEN VOID MODAL
   ========================================================= */

const voidPurchaseModal = document.getElementById("voidPurchaseModal");

if (voidPurchaseModal && voidPurchaseModal.dataset.openOnLoad === "true") {
    const modal = Modal.getOrCreateInstance(voidPurchaseModal);

    modal.show();
}
