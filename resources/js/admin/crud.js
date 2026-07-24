import Swal from "sweetalert2";

/* =========================================================
   CHARACTER COUNTER
   ========================================================= */

document.querySelectorAll("[data-character-counter]").forEach((input) => {
    const counterId = input.dataset.characterCounter;
    const counter = document.getElementById(counterId);

    if (!counter) {
        return;
    }

    const updateCounter = () => {
        const maximum = input.maxLength > 0 ? input.maxLength : 255;

        counter.textContent = `${input.value.length}/${maximum}`;
    };

    input.addEventListener("input", updateCounter);
    updateCounter();
});

/* =========================================================
   CONFIRM ACTION
   ========================================================= */

document.querySelectorAll(".js-confirm-action").forEach((form) => {
    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        const submitButton = this.querySelector('button[type="submit"]');

        const result = await Swal.fire({
            icon: this.dataset.confirmIcon || "warning",
            title: this.dataset.confirmTitle || "Konfirmasi tindakan",
            text:
                this.dataset.confirmMessage ||
                "Apakah Anda yakin ingin melanjutkan?",
            showCancelButton: true,
            confirmButtonText: this.dataset.confirmButton || "Ya, Lanjutkan",
            cancelButtonText: "Batal",
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            customClass: {
                popup: "crud-alert-popup",
                confirmButton:
                    this.dataset.confirmClass || "btn btn-primary px-4",
                cancelButton: "btn btn-outline-secondary px-4",
            },
            buttonsStyling: false,
        });

        if (!result.isConfirmed) {
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Memproses...";
        }

        this.submit();
    });
});

/* =========================================================
   FORM SUBMIT LOADING
   ========================================================= */

document.querySelectorAll(".js-submit-form").forEach((form) => {
    form.addEventListener("submit", function () {
        const button = this.querySelector(".js-submit-button");

        if (!button) {
            return;
        }

        button.disabled = true;
        button.textContent = button.dataset.loadingText || "Menyimpan...";
    });
});

/* =========================================================
   IMAGE PREVIEW
   ========================================================= */

document.querySelectorAll("[data-image-preview-target]").forEach((input) => {
    input.addEventListener("change", async function () {
        const targetId = this.dataset.imagePreviewTarget;
        const preview = document.getElementById(targetId);
        const file = this.files?.[0];

        if (!preview || !file) {
            return;
        }

        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];

        const maximumSize =
            Number.parseInt(this.dataset.maxSize, 10) || 2 * 1024 * 1024;

        if (!allowedTypes.includes(file.type)) {
            this.value = "";

            await Swal.fire({
                icon: "warning",
                title: "Format gambar tidak didukung",
                text: "Gunakan gambar JPG, PNG, atau WEBP.",
                confirmButtonText: "Mengerti",
                customClass: {
                    popup: "crud-alert-popup",
                    confirmButton: "btn btn-primary px-4",
                },
                buttonsStyling: false,
            });

            return;
        }

        if (file.size > maximumSize) {
            this.value = "";

            await Swal.fire({
                icon: "warning",
                title: "Ukuran gambar terlalu besar",
                text: "Ukuran gambar maksimal adalah 2 MB.",
                confirmButtonText: "Mengerti",
                customClass: {
                    popup: "crud-alert-popup",
                    confirmButton: "btn btn-primary px-4",
                },
                buttonsStyling: false,
            });

            return;
        }

        const imageUrl = URL.createObjectURL(file);

        preview.innerHTML = `
                <img
                    src="${imageUrl}"
                    alt="Pratinjau gambar"
                >
            `;
    });
});

/* =========================================================
   PRODUCT FORM PREVIEW
   ========================================================= */

const productBrandInput = document.getElementById("brand");
const productVariantInput = document.getElementById("variant");
const productSizeInput = document.getElementById("size");
const productUnitInput = document.getElementById("unit");

const productCostInput = document.getElementById("cost_price");
const productSellingInput = document.getElementById("price");

const productNamePreview = document.getElementById("productNamePreview");

const productPreviewAvatar = document.getElementById("productPreviewAvatar");

const costPricePreview = document.getElementById("costPricePreview");

const sellingPricePreview = document.getElementById("sellingPricePreview");

const grossProfitPreview = document.getElementById("grossProfitPreview");

const grossMarginPreview = document.getElementById("grossMarginPreview");

function formatRupiah(value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function normalizeProductText(value) {
    return String(value ?? "")
        .trim()
        .replace(/\s+/g, " ")
        .toUpperCase();
}

function updateProductPreview() {
    if (!productNamePreview) {
        return;
    }

    const brand = normalizeProductText(productBrandInput?.value);
    const variant = normalizeProductText(productVariantInput?.value);
    const size = String(productSizeInput?.value ?? "").trim();
    const unit = normalizeProductText(productUnitInput?.value);

    const productName = [
        brand,
        variant,
        size && unit ? `${size} ${unit}` : size || unit,
    ]
        .filter(Boolean)
        .join(" ");

    productNamePreview.textContent = productName || "NAMA PRODUK";

    if (productPreviewAvatar) {
        productPreviewAvatar.textContent = productName.charAt(0) || "P";
    }

    const costPrice = Math.max(Number(productCostInput?.value) || 0, 0);

    const sellingPrice = Math.max(Number(productSellingInput?.value) || 0, 0);

    const grossProfit = Math.max(sellingPrice - costPrice, 0);

    const grossMargin =
        sellingPrice > 0 ? (grossProfit / sellingPrice) * 100 : 0;

    if (costPricePreview) {
        costPricePreview.textContent = formatRupiah(costPrice);
    }

    if (sellingPricePreview) {
        sellingPricePreview.textContent = formatRupiah(sellingPrice);
    }

    if (grossProfitPreview) {
        grossProfitPreview.textContent = formatRupiah(grossProfit);
    }

    if (grossMarginPreview) {
        grossMarginPreview.textContent = `${grossMargin.toLocaleString(
            "id-ID",
            {
                minimumFractionDigits: 0,
                maximumFractionDigits: 1,
            },
        )}%`;
    }
}

[
    productBrandInput,
    productVariantInput,
    productSizeInput,
    productUnitInput,
    productCostInput,
    productSellingInput,
]
    .filter(Boolean)
    .forEach((input) => {
        input.addEventListener("input", updateProductPreview);
        input.addEventListener("change", updateProductPreview);
    });

updateProductPreview();
