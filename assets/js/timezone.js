(function () {
  try {
    var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || "";
    if (!tz) return;

    function setTZCookie(value) {
      var expires = new Date(
        Date.now() + 365 * 24 * 60 * 60 * 1000
      ).toUTCString();
      var cookie =
        "tz=" +
        encodeURIComponent(value) +
        "; path=/; expires=" +
        expires +
        "; samesite=lax";
      if (location.protocol === "https:") cookie += "; secure";
      document.cookie = cookie;
    }

    // aktueller Cookie-Wert
    var m = document.cookie.match(/(?:^|;\s*)tz=([^;]*)/);
    var cur = m ? decodeURIComponent(m[1]) : "";

    if (cur !== tz) {
      // 1) Cookie setzen
      setTZCookie(tz);

      // 2) Prüfen, ob es wirklich gespeichert wurde
      var m2 = document.cookie.match(/(?:^|;\s*)tz=([^;]*)/);
      var ok = m2 && decodeURIComponent(m2[1]) === tz;

      if (!ok) {
        // 3) Cookie geblockt? -> Fallback: tz als Query-Parameter
        var url = new URL(location.href);
        if (url.searchParams.get("tz") !== tz) {
          url.searchParams.set("tz", tz);
          location.replace(url.toString());
        }
        return; // kein Reload, die Seite lädt neu über replace()
      }

      // Cookie erfolgreich -> einmal neu rendern
      location.reload();
    }
  } catch (e) {}
})();
