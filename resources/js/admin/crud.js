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
const productPackagingTypeInput = document.getElementById("packaging_type");
const productSizeInput = document.getElementById("size");
const productUnitInput = document.getElementById("unit");

const productCostInput = document.getElementById("cost_price");
const productSellingInput = document.getElementById("price");

const productNamePreview = document.getElementById("productNamePreview");
const productSkuPreview = document.getElementById("productSkuPreview");

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

function getBrandCode(brand) {
    const clean = brand.replace(/[^A-Z0-9]/g, "");
    return clean.slice(0, 3);
}

function getPackagingCode(packagingType) {
    switch (packagingType) {
        case "BOTOL BESAR":
            return "BB";
        case "BOTOL KECIL":
            return "BK";
        case "KALENG BESAR":
            return "KB";
        case "KALENG KECIL":
            return "KK";
        default:
            return null;
    }
}

function getVariantCode(variant) {
    const words = variant
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (words.length === 0) {
        return "VAR";
    }

    if (words.length === 1) {
        return words[0].replace(/[^A-Z0-9]/g, "").slice(0, 3);
    }

    const w1 = words[0].replace(/[^A-Z0-9]/g, "");
    const w2 = words[1].replace(/[^A-Z0-9]/g, "");

    if (w1.startsWith("ANG")) {
        const w2Char = w2.slice(0, 1);
        return "AG" + w2Char;
    }

    if (w1 === "BIR") {
        if (w2.startsWith("HIT")) {
            return "BHT";
        }
        if (w2.startsWith("PUT")) {
            return "BPT";
        }
        return "B" + w2.slice(0, 2);
    }

    if (words.length >= 3) {
        const w3 = words[2].replace(/[^A-Z0-9]/g, "");
        return w1.slice(0, 1) + w2.slice(0, 1) + w3.slice(0, 1);
    }

    return w1.slice(0, 2) + w2.slice(0, 1);
}

function generateSkuPreview(brand, variant, packagingType, size, unit) {
    if (!brand || !variant || !size || !unit) {
        return null;
    }

    const brandCode = getBrandCode(brand);
    const variantCode = getVariantCode(variant);
    const pkgCode = getPackagingCode(packagingType);

    const segments = [brandCode, variantCode];
    if (pkgCode) {
        segments.push(pkgCode);
    }
    segments.push(`${size}${unit}`);

    return segments.join("-");
}

function updateProductPreview() {
    if (!productNamePreview) {
        return;
    }

    const brand = normalizeProductText(productBrandInput?.value);
    const variant = normalizeProductText(productVariantInput?.value);
    const packagingType = normalizeProductText(productPackagingTypeInput?.value);
    const size = String(productSizeInput?.value ?? "").trim();
    const unit = normalizeProductText(productUnitInput?.value);

    const productName = [
        brand,
        variant,
        packagingType,
        size && unit ? `${size} ${unit}` : size || unit,
    ]
        .filter(Boolean)
        .join(" ");

    productNamePreview.textContent = productName || "NAMA PRODUK";

    if (productSkuPreview) {
        const liveSku = generateSkuPreview(brand, variant, packagingType, size, unit);
        if (liveSku) {
            productSkuPreview.textContent = `SKU ${liveSku}`;
        } else if (!productSkuPreview.dataset.initialSku) {
            productSkuPreview.textContent = "SKU dibuat otomatis saat disimpan";
        }
    }

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
    productPackagingTypeInput,
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

/* =========================================================
   PASSWORD VISIBILITY
   ========================================================= */

document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    button.addEventListener("click", function () {
        const inputId = this.dataset.passwordToggle;
        const input = document.getElementById(inputId);

        if (!input) {
            return;
        }

        const isPassword = input.type === "password";

        input.type = isPassword ? "text" : "password";
        this.textContent = isPassword ? "Tutup" : "Lihat";
    });
});

/* =========================================================
   PASSWORD CONFIRMATION
   ========================================================= */

document
    .querySelectorAll("[data-password-confirmation-for]")
    .forEach((confirmationInput) => {
        const passwordInput = document.getElementById(
            confirmationInput.dataset.passwordConfirmationFor,
        );

        const message = document.getElementById(
            confirmationInput.dataset.passwordMatchTarget,
        );

        if (!passwordInput || !message) {
            return;
        }

        const updatePasswordMatch = () => {
            const password = passwordInput.value;
            const confirmation = confirmationInput.value;

            message.classList.remove("is-valid", "is-invalid");

            if (confirmation === "") {
                message.textContent = "Password harus sama.";

                return;
            }

            if (password === confirmation) {
                message.textContent = "Password sudah sama.";

                message.classList.add("is-valid");

                return;
            }

            message.textContent = "Konfirmasi password belum sama.";

            message.classList.add("is-invalid");
        };

        passwordInput.addEventListener("input", updatePasswordMatch);

        confirmationInput.addEventListener("input", updatePasswordMatch);
    });

/* =========================================================
   RESET PASSWORD MODAL
   ========================================================= */

const resetPasswordModal = document.getElementById("resetPasswordModal");

const resetPasswordForm = document.getElementById("resetPasswordForm");

resetPasswordModal?.addEventListener("show.bs.modal", function (event) {
    const trigger = event.relatedTarget;

    if (!trigger || !resetPasswordForm) {
        return;
    }

    const actionUrl = trigger.dataset.resetPasswordUrl;

    const userName = trigger.dataset.userName || "User";

    const userEmail = trigger.dataset.userEmail || "-";

    resetPasswordForm.action = actionUrl;

    const nameElement = document.getElementById("resetUserName");

    const emailElement = document.getElementById("resetUserEmail");

    const avatarElement = document.getElementById("resetUserAvatar");

    if (nameElement) {
        nameElement.textContent = userName;
    }

    if (emailElement) {
        emailElement.textContent = userEmail;
    }

    if (avatarElement) {
        const initials = userName
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((word) => word.charAt(0).toUpperCase())
            .join("");

        avatarElement.textContent = initials || "U";
    }
});

resetPasswordModal?.addEventListener("hidden.bs.modal", function () {
    if (!resetPasswordForm) {
        return;
    }

    resetPasswordForm.reset();
    resetPasswordForm.action = "#";

    resetPasswordForm
        .querySelectorAll('input[type="text"]')
        .forEach((input) => {
            if (
                input.id === "resetPassword" ||
                input.id === "resetPasswordConfirmation"
            ) {
                input.type = "password";
            }
        });

    resetPasswordForm
        .querySelectorAll("[data-password-toggle]")
        .forEach((button) => {
            button.textContent = "Lihat";
        });

    const matchMessage = document.getElementById("resetPasswordMatch");

    if (matchMessage) {
        matchMessage.textContent = "Minimal 8 karakter.";

        matchMessage.classList.remove("is-valid", "is-invalid");
    }
});
