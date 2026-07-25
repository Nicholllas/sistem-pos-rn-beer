/* =========================================================
   SIDEBAR ACTIVE MENU SCROLL
   ========================================================= */

const ACTIVE_MENU_SELECTOR = [
    ".app-sidebar .nav-link.active",
    ".app-sidebar a.active",
    '.app-sidebar [aria-current="page"]',
].join(", ");

function getVisibleActiveSidebarLink() {
    const activeLinks = [
        ...document.querySelectorAll(ACTIVE_MENU_SELECTOR),
    ].filter((element) => {
        return element.offsetParent !== null;
    });

    /*
     * Ambil active link terakhir.
     * Berguna ketika parent dan child sama-sama memiliki
     * class active.
     */
    return activeLinks.at(-1) || null;
}

function getSidebarScrollContainer(activeLink) {
    if (!activeLink) {
        return null;
    }

    return (
        activeLink.closest("[data-sidebar-scroll]") ||
        document.querySelector("[data-sidebar-scroll]") ||
        activeLink.closest(".sidebar-wrapper") ||
        document.querySelector(".app-sidebar .sidebar-wrapper")
    );
}

function openActiveTreeMenu(activeLink) {
    if (!activeLink) {
        return;
    }

    let treeView = activeLink.closest(".nav-treeview");

    while (treeView) {
        const parentItem = treeView.closest(".nav-item");

        if (!parentItem) {
            break;
        }

        parentItem.classList.add("menu-open");

        treeView = parentItem.parentElement?.closest(".nav-treeview");
    }
}

function isInsideVisibleArea(activeLink, scrollContainer) {
    const activeRect = activeLink.getBoundingClientRect();

    const containerRect = scrollContainer.getBoundingClientRect();

    const safeTop = containerRect.top + 12;

    /*
     * Beri jarak bawah agar menu aktif tidak tertutup
     * card "Sistem Aktif" yang berada di bawah sidebar.
     */
    const safeBottom = containerRect.bottom - 88;

    return activeRect.top >= safeTop && activeRect.bottom <= safeBottom;
}

function scrollSidebarToActiveMenu({ behavior = "auto", force = false } = {}) {
    const activeLink = getVisibleActiveSidebarLink();

    if (!activeLink) {
        return;
    }

    openActiveTreeMenu(activeLink);

    const scrollContainer = getSidebarScrollContainer(activeLink);

    if (!scrollContainer) {
        return;
    }

    if (!force && isInsideVisibleArea(activeLink, scrollContainer)) {
        return;
    }

    const activeRect = activeLink.getBoundingClientRect();

    const containerRect = scrollContainer.getBoundingClientRect();

    const activeOffset = activeRect.top - containerRect.top;

    const availableHeight = scrollContainer.clientHeight - 88;

    /*
     * Posisikan menu aktif mendekati tengah area sidebar
     * yang terlihat.
     */
    const targetScrollTop =
        scrollContainer.scrollTop +
        activeOffset -
        availableHeight / 2 +
        activeRect.height / 2;

    const maximumScrollTop =
        scrollContainer.scrollHeight - scrollContainer.clientHeight;

    const normalizedScrollTop = Math.min(
        Math.max(targetScrollTop, 0),
        Math.max(maximumScrollTop, 0),
    );

    scrollContainer.scrollTo({
        top: normalizedScrollTop,
        behavior,
    });
}

function initializeActiveSidebarScroll() {
    /*
     * Tunggu dua frame agar AdminLTE selesai menghitung
     * tinggi sidebar dan membuka tree menu.
     */
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            scrollSidebarToActiveMenu({
                behavior: "auto",
            });
        });
    });
}

if (document.readyState === "loading") {
    document.addEventListener(
        "DOMContentLoaded",
        initializeActiveSidebarScroll,
        {
            once: true,
        },
    );
} else {
    initializeActiveSidebarScroll();
}

/*
 * Diperlukan ketika browser memulihkan halaman melalui
 * back-forward cache.
 */
window.addEventListener("pageshow", initializeActiveSidebarScroll);
