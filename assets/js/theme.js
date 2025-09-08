var userSettings = {
  Layout: "vertical", // vertical | horizontal
  SidebarType: "mini-sidebar", // full | mini-sidebar
  BoxedLayout: false, // true | false
  Direction: "ltr", // ltr | rtl
  Theme: "light", // light | dark
  ColorTheme: "Hydra", // Blue_Theme | Aqua_Theme | Purple_Theme | Green_Theme | Cyan_Theme | Orange_Theme
  cardBorder: false, // true | false
};

import { Tooltip, Popover } from 'bootstrap';

document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  // =================================
  // Tooltip
  // =================================
  const tooltipTriggerList = Array.from(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.forEach((tooltipTriggerEl) => {
    new Tooltip(tooltipTriggerEl);
  });

  // =================================
  // Popover
  // =================================
  var popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new Popover(popoverTriggerEl);
  });
  // =================================
  // Hide preloader
  // =================================
  var preloader = document.querySelector(".preloader");
  if (preloader) {
    preloader.style.display = "none";
  }
  // =================================
  // Increment & Decrement
  // =================================
  var quantityButtons = document.querySelectorAll(".minus, .add");
  if (quantityButtons) {
    quantityButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        var qtyInput = this.closest("div").querySelector(".qty");
        var currentVal = parseInt(qtyInput.value);
        var isAdd = this.classList.contains("add");

        if (!isNaN(currentVal)) {
          qtyInput.value = isAdd
            ? ++currentVal
            : currentVal > 0
              ? --currentVal
              : currentVal;
        }
      });
    });
  }
  // =================================
  // Fixed header
  // =================================
  window.addEventListener("scroll", function () {
    var topbar = document.querySelector(".topbar");
    if (topbar) {
      if (window.scrollY >= 60) {
        topbar.classList.add("shadow-sm");
      } else {
        topbar.classList.remove("shadow-sm");
      }
    }
  });
});


// Merge default and provided settings
var settings = Object.assign({}, userSettings);

var AdminSettings = {
  // Settings INIT
  AdminSettingsInit: function () {
    AdminSettings.ManageThemeLayout();
    AdminSettings.ManageSidebarType();
    AdminSettings.ManageBoxedLayout();
    AdminSettings.ManageDirectionLayout();
    AdminSettings.ManageDarkThemeLayout();
    AdminSettings.ManageColorThemeLayout();
    AdminSettings.ManageCardLayout();
  },

  //****************************
  // Vertical / Horizontal Layout
  //****************************
  ManageThemeLayout: function () {
    switch (settings.Layout) {
      case "horizontal":
        document.documentElement.setAttribute("data-layout", "horizontal");
        break;
      case "vertical":
        document.documentElement.setAttribute("data-layout", "vertical");
        break;
      default:
    }
  },

  //****************************
  // Full / Minisidebar type
  //****************************
  ManageSidebarType: function () {
    let sidebartype = (null != localStorage.getItem("sidebartype")) ? localStorage.getItem('sidebartype') : settings.SidebarType;

    switch (sidebartype) {
      //****************************
      // If the sidebar type has full
      //****************************
      case "full":
        document.body.setAttribute("data-sidebartype", "full");
        //****************************
        /* This is for the mini-sidebar if width is less then 1170*/
        //****************************
        var setsidebartype = function () {
          var width =
            window.innerWidth > 0 ? window.innerWidth : this.screen.width;
          if (width < 1300) {
            document.body.setAttribute("data-sidebartype", "mini-sidebar");
          } else {
            document.body.setAttribute("data-sidebartype", "full");
          }
        };
        window.addEventListener("DOMContentLoaded", setsidebartype);
        window.addEventListener("resize", setsidebartype);
        break;

      //****************************
      // If the sidebar type has mini-sidebar
      //****************************
      case "mini-sidebar":
        document.body.setAttribute("data-sidebartype", "mini-sidebar");
        break;

      default:
    }
  },

  //****************************
  // Layout Boxed or Full
  //****************************
  ManageBoxedLayout: function () {
    switch (settings.BoxedLayout) {
      case true:
        document.documentElement.setAttribute("data-boxed-layout", "boxed");
        break;
      case false:
        document.documentElement.setAttribute("data-boxed-layout", "full");
        break;
      default:
    }
  },
  //****************************
  // Direction Type
  //****************************
  ManageDirectionLayout: function () {
    switch (settings.Direction) {
      case "ltr":
        document.documentElement.setAttribute("dir", "ltr");
        break;
      case "rtl":
        document.documentElement.setAttribute("dir", "rtl");
        break;
      default:
    }
  },
  //****************************
  // Card Type
  //****************************
  ManageCardLayout: function () {
    switch (settings.cardBorder) {
      case true:
        document.documentElement.setAttribute("data-card", "border");
        break;
      case false:
        document.documentElement.setAttribute("data-card", "shadow");
        break;
      default:
    }
  },
  //****************************
  // Theme Dark or light
  //****************************
  ManageDarkThemeLayout: function () {
    let theme = (null != localStorage.getItem("theme")) ? localStorage.getItem('theme') : settings.Theme;

    switch (theme) {
      case "light":
        setTheme("light", ["moon"], ["sun"]);
        break;
      case "dark":
        setTheme("dark", ["sun"], ["moon"]);
        break;
      default:
    }

    function setTheme(theme, showElements, hideElements) {
        localStorage.setItem("theme", theme);
        document.documentElement.setAttribute("data-bs-theme", theme);
        hideElements.forEach((el) =>
            document
                .querySelectorAll(`.${el}`)
                .forEach((e) => (e.style.display = "none"))
        );
        showElements.forEach((el) =>
            document
                .querySelectorAll(`.${el}`)
                .forEach((e) => (e.style.display = "flex"))
            );
        }
    },

  //****************************
  // Theme color
  //****************************
  ManageColorThemeLayout: function () {
    const { ColorTheme } = settings;
    document.documentElement.setAttribute("data-color-theme", ColorTheme);
  },
};

// Initialize settings
AdminSettings.AdminSettingsInit();



//****************************
// Handle Click
//****************************

document.addEventListener("DOMContentLoaded", function () {
  //****************************
  // Theme mode dark or light
  //****************************

  function handleTheme() {
    function setThemeAttributes(
      theme,
      sunDisplay,
      moonDisplay
    ) {
      localStorage.setItem("theme", theme);
      document.documentElement.setAttribute("data-bs-theme", theme);

      document
        .querySelectorAll(`.${sunDisplay}`)
        .forEach((el) => (el.style.display = "none"));
      document
        .querySelectorAll(`.${moonDisplay}`)
        .forEach((el) => (el.style.display = "flex"));
    }

    document.querySelectorAll(".dark-layout").forEach((element) => {
      element.addEventListener("click", () =>
        setThemeAttributes("dark", "moon", "sun")
      );
    });

    document.querySelectorAll(".light-layout").forEach((element) => {
      element.addEventListener("click", () =>
        setThemeAttributes("light", "sun", "moon")
      );
    });
  }
  handleTheme();

  //****************************
  // Toggle sidebar
  //****************************
  function handleSidebar() {
    document.querySelectorAll(".sidebartoggler").forEach(function (element) {
      element.addEventListener("click", function () {
        document.querySelectorAll(".sidebartoggler").forEach(function (el) {
        });
        document
          .getElementById("main-wrapper")
          .classList.toggle("show-sidebar");
        document.querySelectorAll(".sidebarmenu").forEach(function (el) {
          el.classList.toggle("close");
        });
        var dataTheme = document.body.getAttribute("data-sidebartype");
        if (dataTheme === "full") {
          document.body.setAttribute("data-sidebartype", "mini-sidebar");
          localStorage.setItem('sidebartype', 'mini-sidebar');
        } else {
          document.body.setAttribute("data-sidebartype", "full");
          localStorage.setItem('sidebartype', 'full');
        }
      });
    });
  }

  handleSidebar();
});

var at = document.documentElement.getAttribute("data-layout");
if ((at = "vertical")) {
  // ==============================================================
  // Auto select left navbar
  // ==============================================================

  document.addEventListener("DOMContentLoaded", function () {
    "use strict";
    var isSidebar = document.getElementsByClassName("side-mini-panel");
    if (isSidebar.length > 0) {
      var url = window.location + "";
      var path = url.replace(
        window.location.protocol + "//" + window.location.host + "/",
        ""
      );

      //****************************
      // This is for
      //****************************


      //****************************
      // This is for the multilevel menu
      //****************************
      document.querySelectorAll("#sidebarnav a").forEach(function (link) {
        link.addEventListener("click", function (e) {
          const isActive = this.classList.contains("active");
          const parentUl = this.closest("ul");

          if (!isActive) {
            // hide any open menus and remove all other classes
            parentUl.querySelectorAll("ul").forEach(function (submenu) {
              submenu.classList.remove("in");
            });
            parentUl.querySelectorAll("a").forEach(function (navLink) {
              navLink.classList.remove("active");
            });

            // open our new menu and add the open class
            const submenu = this.nextElementSibling;
            if (submenu) {
              submenu.classList.add("in");
            }

            this.classList.add("active");
          } else {
            this.classList.remove("active");
            parentUl.classList.remove("active");
            const submenu = this.nextElementSibling;
            if (submenu) {
              submenu.classList.remove("in");
            }
          }
        });
      });

      document
        .querySelectorAll("#sidebarnav > li > a.has-arrow")
        .forEach(function (link) {
          link.addEventListener("click", function (e) {
            e.preventDefault();
          });
        });

      //****************************
      // This is for show menu
      //****************************


      function findMatchingElement() {
        var elements = document.querySelector('#sidebarnav a.active');

        if (elements) {
            return elements;
        }

        var currentUrl = window.location.href;
        var anchors = document.querySelectorAll("#sidebarnav a");

        for (var i = 0; i < anchors.length; i++) {
          if (anchors[i].href === currentUrl) {
            return anchors[i];
          }
        }

        return null; // Return null if no matching element is found
      }

      var existsSelectedMini = document.querySelector('.mini-nav-item.selected') != null;

    if (!existsSelectedMini) {
        var elements = findMatchingElement();

        if (elements) {
            // Do something with the matching element
            elements.classList.add("active");
        }

        var closestNav = elements?.closest("nav[class^=sidebar-nav]");
        var menuid = (closestNav && closestNav.id) || "menu-right-mini-0";
        var menu = menuid[menuid.length - 1];

        if (document.getElementById("menu-right-mini-" + menu)) {
          document
              .getElementById("menu-right-mini-" + menu)
              .classList.add("d-block");
          document.getElementById("mini-" + menu).classList.add("selected");
        }
    }


      //****************************
      // This is for mini sidebar
      //****************************
      document
        .querySelectorAll("ul#sidebarnav ul li a.active")
        .forEach(function (link) {
          link.closest("ul").classList.add("in");
          link.closest("ul").parentElement.classList.add("selected");
        });
      document
        .querySelectorAll(".mini-nav .mini-nav-item")
        .forEach(function (item) {
          item.addEventListener("click", function () {
            var id = this.id;
            document
              .querySelectorAll(".mini-nav .mini-nav-item")
              .forEach(function (navItem) {
                navItem.classList.remove("selected");
              });
            this.classList.add("selected");
            document
              .querySelectorAll(".sidebarmenu nav")
              .forEach(function (nav) {
                nav.classList.remove("d-block");
              });
            document
              .getElementById("menu-right-" + id)
              .classList.add("d-block");
            document.body.setAttribute("data-sidebartype", "full");
          });
        });
    }
  });
}
