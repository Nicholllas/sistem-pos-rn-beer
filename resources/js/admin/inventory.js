import Swal from "sweetalert2";

const stockInForm = document.getElementById("stockInForm");
const stockInSubmitButton = document.getElementById("stockInSubmitButton");

const productSelect = document.getElementById("product_id");
const quantityInput = document.getElementById("qty");
const costPriceInput = document.getElementById("cost_price");

const productReference = document.getElementById("stockProductReference");

const referenceCurrent = document.getElementById("stockReferenceCurrent");

const referenceCost = document.getElementById("stockReferenceCost");

const referenceCategory = document.getElementById("stockReferenceCategory");

const productAvatar = document.getElementById("stockProductAvatar");

const productName = document.getElementById("stockProductName");

const productSku = document.getElementById("stockProductSku");

const stockBeforePreview = document.getElementById("stockBeforePreview");

const stockAddedPreview = document.getElementById("stockAddedPreview");

const stockAfterPreview = document.getElementById("stockAfterPreview");

const stockCostPreview = document.getElementById("stockCostPreview");

const stockValuePreview = document.getElementById("stockValuePreview");

let isStockInSubmitting = false;

function formatRupiah(value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function parseNumber(value) {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : 0;
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function getSelectedProduct() {
    if (!productSelect?.value) {
        return null;
    }

    const option = productSelect.options[productSelect.selectedIndex];

    if (!option) {
        return null;
    }

    return {
        id: productSelect.value,
        name: option.dataset.name || option.textContent.trim(),
        sku: option.dataset.sku || "-",
        category: option.dataset.category || "-",
        stock: parseNumber(option.dataset.stock),
        costPrice: parseNumber(option.dataset.costPrice),
    };
}

function updateStockPreview() {
    const product = getSelectedProduct();

    const quantity = Math.max(parseNumber(quantityInput?.value), 0);

    const costPrice = Math.max(parseNumber(costPriceInput?.value), 0);

    const currentStock = product?.stock || 0;
    const finalStock = currentStock + quantity;
    const stockValue = quantity * costPrice;

    if (productReference) {
        productReference.hidden = !product;
    }

    if (referenceCurrent) {
        referenceCurrent.textContent = currentStock.toLocaleString("id-ID");
    }

    if (referenceCost) {
        referenceCost.textContent = formatRupiah(product?.costPrice || 0);
    }

    if (referenceCategory) {
        referenceCategory.textContent = product?.category || "-";
    }

    if (productName) {
        productName.textContent = product?.name || "Pilih Produk";
    }

    if (productSku) {
        productSku.textContent = product
            ? `SKU ${product.sku} · ${product.category}`
            : "Informasi produk akan ditampilkan di sini.";
    }

    if (productAvatar) {
        productAvatar.textContent =
            product?.name?.charAt(0)?.toUpperCase() || "S";
    }

    if (stockBeforePreview) {
        stockBeforePreview.textContent = currentStock.toLocaleString("id-ID");
    }

    if (stockAddedPreview) {
        stockAddedPreview.textContent = quantity.toLocaleString("id-ID");
    }

    if (stockAfterPreview) {
        stockAfterPreview.textContent = finalStock.toLocaleString("id-ID");
    }

    if (stockCostPreview) {
        stockCostPreview.textContent = formatRupiah(costPrice);
    }

    if (stockValuePreview) {
        stockValuePreview.textContent = formatRupiah(stockValue);
    }
}

[productSelect, quantityInput, costPriceInput]
    .filter(Boolean)
    .forEach((input) => {
        input.addEventListener("input", updateStockPreview);
        input.addEventListener("change", updateStockPreview);
    });

stockInForm?.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (isStockInSubmitting) {
        return;
    }

    const product = getSelectedProduct();
    const quantity = parseNumber(quantityInput?.value);
    const costPrice = parseNumber(costPriceInput?.value);

    if (!product) {
        await Swal.fire({
            icon: "warning",
            title: "Produk belum dipilih",
            text: "Pilih produk yang akan ditambahkan stoknya.",
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        productSelect?.focus();

        return;
    }

    if (quantity < 1) {
        await Swal.fire({
            icon: "warning",
            title: "Jumlah stok tidak valid",
            text: "Jumlah stok masuk minimal 1 unit.",
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        quantityInput?.focus();

        return;
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

        return;
    }

    const finalStock = product.stock + quantity;
    const stockValue = quantity * costPrice;

    const result = await Swal.fire({
        icon: "question",
        title: "Simpan stok masuk?",
        html: `
                <div class="pos-confirmation">
                    <div class="pos-confirmation-row">
                        <span>Produk</span>
                        <strong>${escapeHtml(product.name)}</strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Stok awal</span>
                        <strong>${product.stock.toLocaleString("id-ID")}</strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Stok masuk</span>
                        <strong>${quantity.toLocaleString("id-ID")}</strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Stok akhir</span>
                        <strong>${finalStock.toLocaleString("id-ID")}</strong>
                    </div>

                    <div class="pos-confirmation-divider"></div>

                    <div class="pos-confirmation-row">
                        <span>Harga modal</span>
                        <strong>${formatRupiah(costPrice)}</strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Nilai stok masuk</span>
                        <strong>${formatRupiah(stockValue)}</strong>
                    </div>
                </div>
            `,
        showCancelButton: true,
        confirmButtonText: "Ya, Simpan Stok",
        cancelButtonText: "Periksa Kembali",
        reverseButtons: true,
        focusCancel: true,
        allowOutsideClick: false,
        customClass: {
            popup: "crud-alert-popup",
            confirmButton: "btn btn-primary px-4",
            cancelButton: "btn btn-outline-secondary px-4",
        },
        buttonsStyling: false,
    });

    if (!result.isConfirmed) {
        return;
    }

    isStockInSubmitting = true;

    if (stockInSubmitButton) {
        stockInSubmitButton.disabled = true;
        stockInSubmitButton.textContent = "Menyimpan stok...";
    }

    stockInForm.submit();
});

updateStockPreview();

/* =========================================================
   STOCK ADJUSTMENT
   ========================================================= */

const stockAdjustmentForm = document.getElementById("stockAdjustmentForm");

const adjustmentProductSelect = document.getElementById(
    "adjustment_product_id",
);

const adjustmentActualInput = document.getElementById("actual_stock");

const adjustmentNoteInput = document.getElementById("adjustment_note");

const adjustmentSubmitButton = document.getElementById(
    "adjustmentSubmitButton",
);

const adjustmentProductReference = document.getElementById(
    "adjustmentProductReference",
);

const adjustmentReferenceStock = document.getElementById(
    "adjustmentReferenceStock",
);

const adjustmentReferenceMinimum = document.getElementById(
    "adjustmentReferenceMinimum",
);

const adjustmentReferenceCategory = document.getElementById(
    "adjustmentReferenceCategory",
);

const adjustmentInlineResult = document.getElementById(
    "adjustmentInlineResult",
);

const adjustmentInlineSystem = document.getElementById(
    "adjustmentInlineSystem",
);

const adjustmentInlineActual = document.getElementById(
    "adjustmentInlineActual",
);

const adjustmentInlineDifference = document.getElementById(
    "adjustmentInlineDifference",
);

const adjustmentProductAvatar = document.getElementById(
    "adjustmentProductAvatar",
);

const adjustmentProductName = document.getElementById("adjustmentProductName");

const adjustmentProductSku = document.getElementById("adjustmentProductSku");

const adjustmentSystemPreview = document.getElementById(
    "adjustmentSystemPreview",
);

const adjustmentActualPreview = document.getElementById(
    "adjustmentActualPreview",
);

const adjustmentDifferenceIndicator = document.getElementById(
    "adjustmentDifferenceIndicator",
);

const adjustmentDifferenceLabel = document.getElementById(
    "adjustmentDifferenceLabel",
);

const adjustmentDifferencePreview = document.getElementById(
    "adjustmentDifferencePreview",
);

const adjustmentBeforeSummary = document.getElementById(
    "adjustmentBeforeSummary",
);

const adjustmentAfterSummary = document.getElementById(
    "adjustmentAfterSummary",
);

const adjustmentStockStatus = document.getElementById("adjustmentStockStatus");

let isAdjustmentSubmitting = false;

function getAdjustmentProduct() {
    if (!adjustmentProductSelect?.value) {
        return null;
    }

    const option =
        adjustmentProductSelect.options[adjustmentProductSelect.selectedIndex];

    if (!option) {
        return null;
    }

    return {
        id: adjustmentProductSelect.value,
        name: option.dataset.name || option.textContent.trim(),
        sku: option.dataset.sku || "-",
        category: option.dataset.category || "-",
        stock: Math.max(parseNumber(option.dataset.stock), 0),
        minimumStock: Math.max(parseNumber(option.dataset.minimumStock), 0),
    };
}

function formatStockDifference(value) {
    if (value > 0) {
        return `+${value.toLocaleString("id-ID")}`;
    }

    return value.toLocaleString("id-ID");
}

function setDifferenceClass(element, difference) {
    if (!element) {
        return;
    }

    element.classList.remove("is-positive", "is-negative", "is-neutral");

    if (difference > 0) {
        element.classList.add("is-positive");
        return;
    }

    if (difference < 0) {
        element.classList.add("is-negative");
        return;
    }

    element.classList.add("is-neutral");
}

function getAdjustmentStockStatus(actualStock, minimumStock) {
    if (actualStock <= 0) {
        return {
            label: "Stok Habis",
            className: "is-danger",
        };
    }

    if (actualStock <= minimumStock) {
        return {
            label: "Stok Menipis",
            className: "is-warning",
        };
    }

    return {
        label: "Stok Aman",
        className: "is-success",
    };
}

function updateAdjustmentPreview() {
    const product = getAdjustmentProduct();

    const hasActualValue = adjustmentActualInput?.value !== "";

    const actualStock = hasActualValue
        ? Math.max(parseNumber(adjustmentActualInput?.value), 0)
        : 0;

    const systemStock = product?.stock || 0;
    const difference = actualStock - systemStock;

    if (adjustmentProductReference) {
        adjustmentProductReference.hidden = !product;
    }

    if (adjustmentInlineResult) {
        adjustmentInlineResult.hidden = !product || !hasActualValue;
    }

    if (adjustmentReferenceStock) {
        adjustmentReferenceStock.textContent =
            systemStock.toLocaleString("id-ID");
    }

    if (adjustmentReferenceMinimum) {
        adjustmentReferenceMinimum.textContent = (
            product?.minimumStock || 0
        ).toLocaleString("id-ID");
    }

    if (adjustmentReferenceCategory) {
        adjustmentReferenceCategory.textContent = product?.category || "-";
    }

    if (adjustmentProductName) {
        adjustmentProductName.textContent = product?.name || "Pilih Produk";
    }

    if (adjustmentProductSku) {
        adjustmentProductSku.textContent = product
            ? `SKU ${product.sku} · ${product.category}`
            : "Informasi produk akan ditampilkan di sini.";
    }

    if (adjustmentProductAvatar) {
        adjustmentProductAvatar.textContent =
            product?.name?.charAt(0)?.toUpperCase() || "A";
    }

    if (adjustmentInlineSystem) {
        adjustmentInlineSystem.textContent =
            systemStock.toLocaleString("id-ID");
    }

    if (adjustmentInlineActual) {
        adjustmentInlineActual.textContent =
            actualStock.toLocaleString("id-ID");
    }

    if (adjustmentInlineDifference) {
        adjustmentInlineDifference.textContent =
            formatStockDifference(difference);

        setDifferenceClass(adjustmentInlineDifference, difference);
    }

    if (adjustmentSystemPreview) {
        adjustmentSystemPreview.textContent =
            systemStock.toLocaleString("id-ID");
    }

    if (adjustmentActualPreview) {
        adjustmentActualPreview.textContent = hasActualValue
            ? actualStock.toLocaleString("id-ID")
            : "0";
    }

    if (adjustmentBeforeSummary) {
        adjustmentBeforeSummary.textContent =
            systemStock.toLocaleString("id-ID");
    }

    if (adjustmentAfterSummary) {
        adjustmentAfterSummary.textContent = hasActualValue
            ? actualStock.toLocaleString("id-ID")
            : systemStock.toLocaleString("id-ID");
    }

    if (adjustmentDifferencePreview && adjustmentDifferenceLabel) {
        adjustmentDifferencePreview.textContent = hasActualValue
            ? formatStockDifference(difference)
            : "0";

        if (!product || !hasActualValue) {
            adjustmentDifferenceLabel.textContent = "Belum dihitung";
        } else if (difference > 0) {
            adjustmentDifferenceLabel.textContent = "Stok bertambah";
        } else if (difference < 0) {
            adjustmentDifferenceLabel.textContent = "Stok berkurang";
        } else {
            adjustmentDifferenceLabel.textContent = "Stok sudah sesuai";
        }
    }

    if (adjustmentDifferenceIndicator) {
        setDifferenceClass(
            adjustmentDifferenceIndicator,
            hasActualValue ? difference : 0,
        );
    }

    if (adjustmentStockStatus) {
        adjustmentStockStatus.classList.remove(
            "is-success",
            "is-warning",
            "is-danger",
            "is-neutral",
        );

        if (!product || !hasActualValue) {
            adjustmentStockStatus.textContent = "Belum diketahui";

            adjustmentStockStatus.classList.add("is-neutral");
        } else {
            const status = getAdjustmentStockStatus(
                actualStock,
                product.minimumStock,
            );

            adjustmentStockStatus.textContent = status.label;

            adjustmentStockStatus.classList.add(status.className);
        }
    }
}

adjustmentProductSelect?.addEventListener("change", function () {
    const product = getAdjustmentProduct();

    if (
        product &&
        adjustmentActualInput &&
        adjustmentActualInput.value === ""
    ) {
        adjustmentActualInput.value = product.stock;
    }

    updateAdjustmentPreview();
});

adjustmentActualInput?.addEventListener("input", updateAdjustmentPreview);

stockAdjustmentForm?.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (isAdjustmentSubmitting) {
        return;
    }

    const product = getAdjustmentProduct();

    const actualStock = parseNumber(adjustmentActualInput?.value);

    const note = adjustmentNoteInput?.value.trim() || "";

    if (!product) {
        await Swal.fire({
            icon: "warning",
            title: "Produk belum dipilih",
            text: "Pilih produk yang stoknya akan disesuaikan.",
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        adjustmentProductSelect?.focus();

        return;
    }

    if (adjustmentActualInput?.value === "" || actualStock < 0) {
        await Swal.fire({
            icon: "warning",
            title: "Stok fisik tidak valid",
            text: "Masukkan stok fisik aktual dengan nilai minimal 0.",
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        adjustmentActualInput?.focus();

        return;
    }

    if (!note) {
        await Swal.fire({
            icon: "warning",
            title: "Alasan belum diisi",
            text: "Jelaskan alasan penyesuaian untuk kebutuhan audit stok.",
            confirmButtonText: "Mengerti",
            customClass: {
                popup: "crud-alert-popup",
                confirmButton: "btn btn-primary px-4",
            },
            buttonsStyling: false,
        });

        adjustmentNoteInput?.focus();

        return;
    }

    const difference = actualStock - product.stock;

    const differenceLabel =
        difference > 0
            ? "Penambahan stok"
            : difference < 0
              ? "Pengurangan stok"
              : "Tidak ada selisih";

    const confirmationIcon = difference === 0 ? "info" : "warning";

    const result = await Swal.fire({
        icon: confirmationIcon,
        title: "Simpan penyesuaian stok?",
        html: `
                <div class="pos-confirmation">
                    <div class="pos-confirmation-row">
                        <span>Produk</span>
                        <strong>${escapeHtml(product.name)}</strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Stok sistem</span>
                        <strong>
                            ${product.stock.toLocaleString("id-ID")}
                        </strong>
                    </div>

                    <div class="pos-confirmation-row">
                        <span>Stok fisik</span>
                        <strong>
                            ${actualStock.toLocaleString("id-ID")}
                        </strong>
                    </div>

                    <div class="pos-confirmation-divider"></div>

                    <div class="pos-confirmation-row">
                        <span>${differenceLabel}</span>
                        <strong>
                            ${formatStockDifference(difference)}
                        </strong>
                    </div>
                </div>
            `,
        showCancelButton: true,
        confirmButtonText: "Ya, Simpan Penyesuaian",
        cancelButtonText: "Periksa Kembali",
        reverseButtons: true,
        focusCancel: true,
        allowOutsideClick: false,
        customClass: {
            popup: "crud-alert-popup",
            confirmButton: "btn btn-primary px-4",
            cancelButton: "btn btn-outline-secondary px-4",
        },
        buttonsStyling: false,
    });

    if (!result.isConfirmed) {
        return;
    }

    isAdjustmentSubmitting = true;

    if (adjustmentSubmitButton) {
        adjustmentSubmitButton.disabled = true;
        adjustmentSubmitButton.textContent = "Menyimpan penyesuaian...";
    }

    stockAdjustmentForm.submit();
});

updateAdjustmentPreview();
