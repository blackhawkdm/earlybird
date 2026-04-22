/**
 * Paul Bunyan Plumbing — Location-Based Header Swap
 */
(function () {
  var COOKIE_NAME = "client_region";
  var DEFAULT_CITY = "Rochester";
  var GEO_API = "http://ip-api.com/json/?fields=status,zip";

  // Minneapolis service area zip codes — everything else defaults to Rochester.
  var MINNEAPOLIS_ZIPS = [
    "55001","55003","55005","55009","55011","55013","55014","55016","55019","55020",
    "55024","55025","55033","55038","55042","55043","55044","55046","55047","55054",
    "55055","55056","55057","55066","55068","55070","55071","55073","55075","55076",
    "55077","55079","55082","55088","55089","55090","55092","55101","55102","55103",
    "55104","55105","55106","55107","55108","55109","55110","55111","55112","55113",
    "55114","55115","55116","55117","55118","55119","55120","55121","55122","55123",
    "55124","55125","55126","55127","55128","55129","55130","55150","55155","55301",
    "55303","55304","55305","55306","55308","55309","55311","55313","55315","55316",
    "55317","55318","55327","55328","55330","55331","55337","55340","55341","55343",
    "55344","55345","55346","55347","55352","55356","55357","55358","55359","55362",
    "55363","55364","55369","55371","55372","55373","55374","55375","55376","55378",
    "55379","55384","55386","55387","55388","55391","55398","55401","55402","55403",
    "55404","55405","55406","55407","55408","55409","55410","55411","55412","55413",
    "55414","55415","55416","55417","55418","55419","55420","55421","55422","55423",
    "55424","55425","55426","55427","55428","55429","55430","55431","55432","55433",
    "55434","55435","55436","55437","55438","55439","55441","55442","55443","55444",
    "55445","55446","55447","55448","55449","55450","55454","55455","56011","56071"
  ];

  // Rochester service area zip codes.
  var ROCHESTER_ZIPS = [
    "55018","55021","55026","55027","55031","55041","55049","55052","55053","55060",
    "55065","55085","55087","55901","55902","55903","55904","55905","55906","55910",
    "55912","55917","55918","55920","55923","55924","55926","55927","55929","55932",
    "55933","55934","55935","55936","55940","55944","55945","55946","55949","55950",
    "55952","55955","55956","55957","55959","55960","55963","55964","55967","55968",
    "55969","55972","55973","55975","55976","55979","55981","55982","55983","55985",
    "55987","55990","55991","55992"
  ];

  var locationData = {
    Minneapolis: {
      city: "Minneapolis",
      services: "Expert Plumber in Minneapolis",
      address:
        '5720 International Parkway <br> <span class="address-line2">New Hope, MN 55428</span>',
      phone: "612-340-1444",
      phone_link: "tel:6123401444",
      booking: "/minneapolis/service-areas/",
      url_prefix: "/minneapolis",
      water_heaters: "/minneapolis/services/water-heaters/",
      drain_sewer: "/minneapolis/services/drains-sewers/",
      plumbing: "/minneapolis/services/plumbing/",
      water_quality: "/minneapolis/services/water-quality/",
      leak_detection: "/minneapolis/services/other-services/leak-repair/",
      btn_label_phone: "(612) 340-1444",
      btn_label_booking: "Book in Minneapolis",
    },
    Rochester: {
      city: "Rochester",
      services: "Expert Plumber in Rochester",
      address:
        '4410 19th Street NW <br> <span class="address-line2">Rochester, MN 55901</span>',
      phone: "507-821-3664",
      phone_link: "tel:5078213664",
      booking: "/rochester/service-areas/",
      url_prefix: "/rochester",
      water_heaters: "/rochester/services/water-heaters/",
      drain_sewer: "/rochester/services/drains-sewers/",
      plumbing: "/rochester/services/plumbing/",
      water_quality: "/rochester/services/water-quality/",
      leak_detection: "/rochester/services/other-services/leak-repair/",
      btn_label_phone: "(507) 821-3664",
      btn_label_booking: "Book in Rochester",
    },
  };

  var LOCATION_SLUGS = ["minneapolis", "rochester"];

  /* — Cookie helpers — */
  function getCookie(name) {
    var match = document.cookie.match(
      new RegExp("(?:^|;\\s*)" + name + "=([^;]*)")
    );
    return match ? decodeURIComponent(match[1]) : null;
  }

  function setCookie(name, value, days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 86400000);
    document.cookie =
      name + "=" + encodeURIComponent(value) + ";expires=" + d.toUTCString() + ";path=/;SameSite=Lax";
  }

  /* — Detect: cookie → localStorage → geo API — */
  function detectCity(callback) {
    var cookieVal = getCookie(COOKIE_NAME);
    if (cookieVal) {
      var normalized =
        cookieVal.charAt(0).toUpperCase() + cookieVal.slice(1).toLowerCase();
      if (locationData[normalized]) {
        callback(normalized);
        return;
      }
    }

    var stored = localStorage.getItem("pb_selected_city");
    if (stored && locationData[stored]) {
      setCookie(COOKIE_NAME, stored, 30);
      callback(stored);
      return;
    }

    // 3. Geo API fallback — resolve by zip code
    fetch(GEO_API)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var city = DEFAULT_CITY;
        if (data.status === "success" && data.zip) {
          if (MINNEAPOLIS_ZIPS.indexOf(data.zip) !== -1) {
            city = "Minneapolis";
          } else if (ROCHESTER_ZIPS.indexOf(data.zip) !== -1) {
            city = "Rochester";
          }
        }
        setCookie(COOKIE_NAME, city, 30);
        localStorage.setItem("pb_selected_city", city);
        callback(city);
      })
      .catch(function () {
        setCookie(COOKIE_NAME, DEFAULT_CITY, 30);
        callback(DEFAULT_CITY);
      });
  }

  /* — Swap: apply location data to the DOM — */
  function applyLocation(cityKey) {
    var loc = locationData[cityKey];
    if (!loc) return;

    localStorage.setItem("pb_selected_city", cityKey);

    /* A: Swap .loc-dynamic-data spans/links by data-field */
    document.querySelectorAll(".loc-dynamic-data").forEach(function (el) {
      var field = el.getAttribute("data-field");
      if (field === "phone_inline") {
        el.setAttribute("href", loc.phone_link);
        el.textContent = loc.phone;
      } else if (field && loc[field] !== undefined) {
        if (field === "address") {
          el.innerHTML = loc[field];
        } else {
          el.textContent = loc[field];
        }
      }
    });

    /* B: Swap .loc-dynamic-link[data-service] hrefs */
    document
      .querySelectorAll(".loc-dynamic-link[data-service]")
      .forEach(function (el) {
        var service = el.getAttribute("data-service");
        if (service && loc[service]) {
          el.setAttribute("href", loc[service]);
        }
      });

    /* C: Catch-all — rewrite any <a> with a location slug */
    var slugPattern = new RegExp(
      "\\/(" + LOCATION_SLUGS.join("|") + ")\\/",
      "i"
    );
    var targetSlug = loc.url_prefix.replace(/^\//, "");

    document.querySelectorAll("a[href]").forEach(function (el) {
      var href = el.getAttribute("href");
      if (href && slugPattern.test(href)) {
        var newHref = href.replace(slugPattern, "/" + targetSlug + "/");
        if (newHref !== href) {
          el.setAttribute("href", newHref);
        }
      }
    });

    /* D: Swap .loc-dynamic-btn buttons */
    document.querySelectorAll(".loc-dynamic-btn").forEach(function (el) {
      var type = el.getAttribute("data-type");
      if (type === "phone") {
        el.setAttribute("href", loc.phone_link);
        var textEl = el.querySelector(".elementor-button-text");
        if (textEl) textEl.textContent = loc.btn_label_phone;
      } else if (type === "booking") {
        el.setAttribute("href", loc.booking);
        var textEl2 = el.querySelector(".elementor-button-text");
        if (textEl2) textEl2.textContent = loc.btn_label_booking;
      }
    });
  }

  /* — Init — */
  function init() {
    detectCity(applyLocation);

    var switcher = document.getElementById("blueox-location-switcher");
    if (switcher) {
      switcher.addEventListener("change", function () {
        var city = this.value;
        setCookie(COOKIE_NAME, city, 30);
        localStorage.setItem("pb_selected_city", city);
        applyLocation(city);
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
