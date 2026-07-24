import Swal from "sweetalert2";
import { Modal } from "bootstrap";

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

let purchaseProducts = [];
let oldPurchaseItems = [];
let purchaseItemIndex = 0;
let isPurchaseSubmitting = false;

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
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function formatRupiah(value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function parseInteger(value) {
    const number = Number.parseInt(value, 10);

    return Number.isFinite(number) ? number : 0;
}

function parseAmount(value) {
    const number = Number.parseFloat(value);

    return Number.isFinite(number) ? number : 0;
}

function buildProductOptions(selectedProductId = "") {
    let options = `
        <option value="">
            Pilih produk
        </option>
    `;

    purchaseProducts.forEach((product) => {
        const selected =
            String(product.id) === String(selectedProductId) ? "selected" : "";

        const category = product.category
            ? ` · ${escapeHtml(product.category)}`
            : "";

        options += `
            <option
                value="${product.id}"
                data-cost-price="${product.cost_price}"
                ${selected}
            >
                ${escapeHtml(product.name)}
                · ${escapeHtml(product.sku || "-")}
                · Stok ${product.stock}
                ${category}
            </option>
        `;
    });

    return options;
}

function createPurchaseRow(item = {}) {
    const rowIndex = purchaseItemIndex++;

    const productId = item.product_id ?? "";
    const quantity = item.qty ?? 1;
    const costPrice = item.cost_price ?? "";

    const row = document.createElement("tr");
    row.className = "purchase-item-row";

    row.innerHTML = `
        <td>
            <select
                name="items[${rowIndex}][product_id]"
                class="form-select crud-form-control purchase-product-select"
                required
            >
                ${buildProductOptions(productId)}
            </select>
        </td>

        <td>
            <input
                type="number"
                name="items[${rowIndex}][qty]"
                class="form-control crud-form-control purchase-qty-input"
                min="1"
                step="1"
                value="${escapeHtml(quantity)}"
                required
            >
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>

                <input
                    type="number"
                    name="items[${rowIndex}][cost_price]"
                    class="form-control crud-form-control purchase-cost-input"
                    min="0"
                    step="1"
                    value="${escapeHtml(costPrice)}"
                    placeholder="0"
                    required
                >
            </div>
        </td>

        <td class="purchase-subtotal">
            Rp 0
        </td>

        <td class="text-end">
            <button
                type="button"
                class="purchase-remove-button"
                aria-label="Hapus produk"
                title="Hapus produk"
            >
                &times;
            </button>
        </td>
    `;

    purchaseItemsBody?.appendChild(row);
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

    const subtotal = Math.max(quantity, 0) * Math.max(costPrice, 0);

    const subtotalElement = row.querySelector(".purchase-subtotal");

    if (subtotalElement) {
        subtotalElement.textContent = formatRupiah(subtotal);
    }
}

function getPurchaseSummary() {
    let itemCount = 0;
    let totalQuantity = 0;
    let grandTotal = 0;

    getPurchaseRows().forEach((row) => {
        const { productId, quantity, costPrice } = getRowValues(row);

        if (productId) {
            itemCount++;
        }

        totalQuantity += Math.max(quantity, 0);

        grandTotal += Math.max(quantity, 0) * Math.max(costPrice, 0);
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
}

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

async function validatePurchaseForm() {
    const supplierInput = document.getElementById("supplier_id");

    if (!supplierInput?.value) {
        await Swal.fire({
            icon: "warning",
            title: "Supplier belum dipilih",
            text: "Pilih supplier untuk transaksi pembelian ini.",
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        supplierInput?.focus();

        return false;
    }

    const rows = getPurchaseRows();

    if (rows.length === 0) {
        await Swal.fire({
            icon: "warning",
            title: "Produk belum ditambahkan",
            text: "Tambahkan minimal satu produk ke pembelian.",
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        return false;
    }

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
            await Swal.fire({
                icon: "warning",
                title: "Produk belum dipilih",
                text: "Pastikan setiap baris memiliki produk.",
                confirmButtonText: "Mengerti",
                customClass: {
                    popup: "crud-alert-popup",
                    confirmButton: "btn btn-primary px-4",
                },
                buttonsStyling: false,
            });

            productSelect?.focus();

            return false;
        }

        if (quantity < 1) {
            await Swal.fire({
                icon: "warning",
                title: "Qty tidak valid",
                text: "Jumlah pembelian minimal 1 unit.",
                confirmButtonText: "Mengerti",
                customClass: {
                    popup: "crud-alert-popup",
                    confirmButton: "btn btn-primary px-4",
                },
                buttonsStyling: false,
            });

            quantityInput?.focus();

            return false;
        }

        if (costPrice < 0) {
            await Swal.fire({
                icon: "warning",
                title: "Harga modal tidak valid",
                text: "Harga modal tidak boleh bernilai negatif.",
                confirmButtonText: "Mengerti",
                customClass: {
                    popup: "crud-alert-popup",
                    confirmButton: "btn btn-primary px-4",
                },
                buttonsStyling: false,
            });

            costPriceInput?.focus();

            return false;
        }
    }

    if (getDuplicateProductIds().length > 0) {
        await Swal.fire({
            icon: "warning",
            title: "Produk tercatat lebih dari sekali",
            text: "Gabungkan produk yang sama menjadi satu baris dan sesuaikan qty-nya.",
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        return false;
    }

    return true;
}

addPurchaseItemButton?.addEventListener("click", function () {
    const newRow = createPurchaseRow();

    newRow.querySelector(".purchase-product-select")?.focus();
});

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

        await Swal.fire({
            icon: "warning",
            title: "Produk sudah ditambahkan",
            text: `${productName} sudah ada di daftar pembelian.`,
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        productSelect.focus();
        updatePurchaseRow(row);
        updatePurchaseSummary();

        return;
    }

    const costPriceInput = row.querySelector(".purchase-cost-input");

    const selectedOption = productSelect.options[productSelect.selectedIndex];

    const defaultCostPrice = parseAmount(selectedOption?.dataset.costPrice);

    if (
        costPriceInput &&
        parseAmount(costPriceInput.value) <= 0 &&
        defaultCostPrice > 0
    ) {
        costPriceInput.value = Math.round(defaultCostPrice);
    }

    updatePurchaseRow(row);
    updatePurchaseSummary();
});

purchaseItemsBody?.addEventListener("input", function (event) {
    const row = event.target.closest(".purchase-item-row");

    if (!row) {
        return;
    }

    if (event.target.matches(".purchase-qty-input, .purchase-cost-input")) {
        updatePurchaseRow(row);
        updatePurchaseSummary();
    }
});

purchaseItemsBody?.addEventListener("click", function (event) {
    const removeButton = event.target.closest(".purchase-remove-button");

    if (!removeButton) {
        return;
    }

    const row = removeButton.closest(".purchase-item-row");

    row?.remove();

    if (getPurchaseRows().length === 0) {
        createPurchaseRow();
    }

    updatePurchaseSummary();
});

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

    const supplierSelect = document.getElementById("supplier_id");

    const supplierName =
        supplierSelect?.options[
            supplierSelect.selectedIndex
        ]?.textContent?.trim() || "-";

    const result = await Swal.fire({
        icon: "question",
        title: "Simpan pembelian stok?",
        html: `
                <div class="pos-confirmation">
                    <div class="pos-confirmation-row">
                        <span>Supplier</span>
                        <strong>${escapeHtml(supplierName)}</strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Jenis produk</span>
                        <strong>${itemCount}</strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Total qty</span>
                        <strong>${totalQuantity}</strong>
                    </div>

                    <div class="pos-confirmation-divider"></div>

                    <div class="pos-confirmation-row">
                        <span>Total pembelian</span>
                        <strong>${formatRupiah(grandTotal)}</strong>
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

    purchaseForm.submit();
});

/* =========================================================
   INITIAL PURCHASE ITEMS
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
   AUTO OPEN VOID MODAL AFTER VALIDATION ERROR
   ========================================================= */

const voidPurchaseModal = document.getElementById("voidPurchaseModal");

if (voidPurchaseModal && voidPurchaseModal.dataset.openOnLoad === "true") {
    const modal = Modal.getOrCreateInstance(voidPurchaseModal);

    modal.show();
}
