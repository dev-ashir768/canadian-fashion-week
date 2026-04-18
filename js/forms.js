function showToast(firstName) {
  var existing = document.getElementById("cfw-toast");
  if (existing) existing.remove();

  var toast = document.createElement("div");
  toast.id = "cfw-toast";
  Object.assign(toast.style, {
    position: "fixed",
    bottom: "2rem",
    right: "2rem",
    background: "#000",
    color: "#fff",
    padding: "1.25rem 2rem",
    fontFamily: "Poppins, sans-serif",
    fontSize: "12px",
    letterSpacing: "0.25em",
    textTransform: "uppercase",
    zIndex: "99999",
    opacity: "0",
    transform: "translateY(1rem)",
    transition: "opacity 0.4s ease, transform 0.4s ease",
    maxWidth: "380px",
    lineHeight: "1.6",
  });
  toast.textContent = "Thank you, " + firstName + ". We\u2019ll be in touch.";
  document.body.appendChild(toast);

  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      toast.style.opacity = "1";
      toast.style.transform = "translateY(0)";
    });
  });

  setTimeout(function () {
    toast.style.opacity = "0";
    toast.style.transform = "translateY(1rem)";
    setTimeout(function () { toast.remove(); }, 400);
  }, 4000);
}

document.addEventListener("DOMContentLoaded", function () {
  if (window.Parsley) {
    window.Parsley.setDefaults({
      trigger: "input change",
      errorsWrapper: '<ul class="parsley-errors-list"></ul>',
      errorTemplate: "<li></li>",
    });
    window.Parsley.addMessages("en", {
      defaultMessage: "This value seems to be invalid.",
      type: {
        email: "Please enter a valid email address.",
      },
      required: "This field is required.",
    });
    window.Parsley.setLocale("en");
  }

  document.querySelectorAll("form[data-cfw-form]").forEach(function (form) {
    var pForm = window.Parsley ? window.Parsley(form) : null;

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      if (pForm && !pForm.validate()) return;

      var values = {};
      form.querySelectorAll("input[name], textarea[name], select[name]").forEach(function (el) {
        values[el.name] = el.value.trim();
      });

      console.log("[CFW] Form submitted:", values);
      showToast(values.firstName || "Guest");
      form.reset();
      if (pForm) pForm.reset();
    });
  });
});
