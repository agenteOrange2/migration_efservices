(function () {
    "use strict";

    function initRaze() {
        // jQuery ya está cargado desde CDN en el head
        const $ = window.jQuery || window.$;
        
        if (!$ || typeof $.fn === 'undefined') {
            console.error('Raze: jQuery not available');
            return;
        }

        // Verificar que SimpleBar esté disponible
        if (typeof window.SimpleBar === 'undefined') {
            // Esperar a que SimpleBar se cargue
            setTimeout(initRaze, 100);
            return;
        }

        // Loading Page
        setTimeout(() => {
            $(".loading-page").addClass("loading-page--hide");
            setTimeout(() => {
                $(".loading-page").removeClass("loading-page--before-hide");
                $(".loading-page").removeClass("loading-page--hide");
            }, 300);
        }, 200);

        // Side Menu Dropdown
        $(".side-menu__link").on("click", function () {
            if ($(this).parent().find("ul").length) {
                if ($(this).parent().find("ul").first()[0].offsetParent !== null) {
                    $(this)
                        .find(".side-menu__link__chevron")
                        .removeClass("transform rotate-180");
                    $(this).removeClass("side-menu__link--active-dropdown");
                    $(this)
                        .parent()
                        .find("ul")
                        .first()
                        .slideUp(300, function () {
                            $(this).removeClass("side-menu__link__sub-open");
                        });
                } else {
                    $(this)
                        .find(".side-menu__link__chevron")
                        .addClass("transform rotate-180");
                    $(this).addClass("side-menu__link--active-dropdown");
                    $(this)
                        .parent()
                        .find("ul")
                        .first()
                        .slideDown(300, function () {
                            $(this).addClass("side-menu__link__sub-open");
                        });
                }
            }
        });

        // Side Menu Scrollbar
        const scrollableRef = $(".scrollable-ref").first()[0];
        if (scrollableRef) {
            try {
                new window.SimpleBar(scrollableRef);
            } catch (e) {
                console.warn('Raze: Error initializing SimpleBar:', e);
            }
        }

        // Side Menu Collapse
        const compactMenu = localStorage.getItem("compactMenu");
        if (compactMenu === null || compactMenu === "true") {
            localStorage.setItem("compactMenu", "true");
            $(".side-menu").first().addClass("side-menu--collapsed");
            $(".content").first().addClass("content--compact");
        } else {
            $(".side-menu").first().removeClass("side-menu--collapsed");
            $(".content").first().removeClass("content--compact");
        }

        // Side Menu Hover
        $(".side-menu .side-menu__content").each(function () {
            $(this).on("mouseover", function () {
                $(".side-menu").first().addClass("side-menu--on-hover");
            });
        });

        $(".side-menu .side-menu__content").each(function () {
            $(this).on("mouseleave", function () {
                $(".side-menu").first().removeClass("side-menu--on-hover");
            });
        });

        // Toggle Compact Menu
        $(".toggle-compact-menu")
            .first()
            .on("click", function (event) {
                event.preventDefault();
                const compactMenu = localStorage.getItem("compactMenu");
                if (compactMenu === null || compactMenu === "true") {
                    localStorage.setItem("compactMenu", "false");
                    $(".side-menu").first().removeClass("side-menu--collapsed");
                    $(".content").first().removeClass("content--compact");
                } else {
                    localStorage.setItem("compactMenu", "true");
                    $(".side-menu").first().addClass("side-menu--collapsed");
                    $(".content").first().addClass("content--compact");
                }
            });

        // Toggle Mobile Menu
        $(".open-mobile-menu")
            .first()
            .on("click", function (event) {
                event.preventDefault();
                $(".side-menu").first().addClass("side-menu--mobile-menu-open");
                $(".close-mobile-menu")
                    .first()
                    .addClass("close-mobile-menu--mobile-menu-open");
            });

        $(".close-mobile-menu")
            .first()
            .on("click", function (event) {
                event.preventDefault();
                $(".side-menu").first().removeClass("side-menu--mobile-menu-open");
                $(".close-mobile-menu")
                    .first()
                    .removeClass("close-mobile-menu--mobile-menu-open");
            });

        // Request full screen
        $(".request-full-screen")
            .first()
            .on("click", function (event) {
                event.preventDefault();
                const el = document.documentElement;
                if (el.requestFullscreen) {
                    el.requestFullscreen();
                }
            });

        // On resize
        window.onresize = () => {
            // Remove "mobile-menu-open" modifier class
            $(".side-menu").first().removeClass("side-menu--mobile-menu-open");
            $(".close-mobile-menu")
                .first()
                .removeClass("close-mobile-menu--mobile-menu-open");

            // Responsive Side Menu
            if (window.innerWidth <= 1600) {
                localStorage.setItem("compactMenu", "true");
                $(".side-menu").first().addClass("side-menu--collapsed");
                $(".content").first().addClass("content--compact");
            }
        };
    }

    // Iniciar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRaze);
    } else {
        // DOM ya está listo
        initRaze();
    }
})();
