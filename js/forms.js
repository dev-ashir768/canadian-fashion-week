(function ($) {
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
      setTimeout(function () {
        toast.remove();
      }, 400);
    }, 4000);
  }

  $(function () {
    // Parsley Configuration
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

    // Initialize all forms with data-cfw-form
    $("form[data-cfw-form]").each(function () {
      var $form = $(this);
      var pInstance = $form.parsley();

      $form.on("submit", function (e) {
        // ALWAYS prevent default first to stop redirects
        e.preventDefault();

        // Manually trigger validation
        if (pInstance.validate()) {
          var values = {};
          $form.find("input[name], textarea[name], select[name]").each(function () {
            values[this.name] = $(this).val().trim();
          });

          console.log("[CFW] Validated Form Submission Success:", values);
          
          // Show the success toast
          showToast(values.firstName || "Guest");

          // Reset the form and Parsley state
          $form[0].reset();
          pInstance.reset();
        } else {
          console.log("[CFW] Form Validation Failed");
        }
      });
    });
  });
})(jQuery);



