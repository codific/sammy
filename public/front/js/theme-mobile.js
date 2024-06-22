// This is custom file and it doesn't belong to the theme

$(document).ready(function () {
    const MOBILE_BREAKPOINT_WIDTH_FROM_THEME = 991;
    const LEFT_NAVBAR_SELECTOR = ".app-header-left";

    $(".mobile-toggle-header-nav").click(function () {
        if ($(LEFT_NAVBAR_SELECTOR).length !== 0) {
            let currentClasses = $(LEFT_NAVBAR_SELECTOR).prop("classList");
            $(LEFT_NAVBAR_SELECTOR).attr("data-old-class", currentClasses).removeClass("app-header-left").addClass("app-header-right");
            $(".app-header__content").addClass("auto-size").addClass("mobile-nav-flex");
            $(".app-header-right").addClass("mobile-nav-flex");
            $(".app-header-right ul").addClass("mobile-nav-flex");
            $(".user-menu-nav").addClass("user-menu-nav-mobile");
        }
    });

    $(window).on("resize", function () {
        let width = document.body.clientWidth;
        if (width >= MOBILE_BREAKPOINT_WIDTH_FROM_THEME) {
            let leftNavbarElement = $("[data-old-class='app-header-left']");
            let oldClasses = $(leftNavbarElement).attr("data-old-class");
            let currentClasses = $(leftNavbarElement).prop("classList");
            $(leftNavbarElement).attr("class", oldClasses).attr("data-old-class", currentClasses);
            $(".app-header__content").removeClass("auto-size").removeClass("mobile-nav-flex");
            $(".app-header-right").removeClass("mobile-nav-flex");
            $(".app-header-right ul").removeClass("mobile-nav-flex");
            $(".user-menu-nav").removeClass("user-menu-nav-mobile");
            $("ul").removeClass("mobile-nav-flex");
        }
    });

    $(".mobile-toggle-sidebar").click(function () {
        $(".app-container").toggleClass("sidebar-mobile-open");
    });
});