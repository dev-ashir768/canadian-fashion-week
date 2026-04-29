(function ($) {
  function showToast(firstName) {
    var existing = document.getElementById("cfp-toast");
    if (existing) existing.remove();

    var toast = document.createElement("div");
    toast.id = "cfp-toast";
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

    // Initialize all forms with data-cfp-form using event delegation
    $(document).on("submit", "form[data-cfp-form]", function (e) {
      // ALWAYS prevent default first to stop redirects
      e.preventDefault();
      
      var $form = $(this);
      var pInstance = $form.parsley();

      // Manually trigger validation
      if (pInstance.validate()) {
        var $submitBtn = $form.find('button[type="submit"]');
        var originalBtnText = $submitBtn.text();

        // Disable button and show loading state
        $submitBtn.prop("disabled", true).text("SENDING...");

        var formData = new FormData(this);
        // Add form type based on nearest heading or context if not specified
        var formTitle =
          $form.prevAll("h1, h2").first().text() ||
          $form.parent().prevAll("h1, h2").first().text() ||
          $form.closest("main, section").find("h1, h2").first().text() ||
          "Website Form";
        formData.append("form_type", formTitle.trim());

        $.ajax({
          url: "includes/process-form.php",
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json', // Explicitly expect JSON
          success: function (response) {
            console.log("[CFP] Form Submission Success:", response);

            if (response.status === "success") {
              if (typeof Swal !== "undefined") {
                Swal.fire({
                  title: "THANK YOU!",
                  text: response.message,
                  icon: "success",
                  confirmButtonColor: "#000",
                  customClass: {
                    popup: "rounded-2xl",
                    confirmButton: "rounded-full px-8 py-3 uppercase tracking-widest text-xs",
                  },
                });
              } else {
                var firstName = $form.find('input[name="firstName"]').val() || "Guest";
                showToast(firstName);
              }

              // Reset the form and Parsley state
              $form[0].reset();
              pInstance.reset();
            } else {
              if (typeof Swal !== "undefined") {
                Swal.fire({
                  title: "ERROR",
                  text: response.message || "Something went wrong.",
                  icon: "error",
                  confirmButtonColor: "#000",
                });
              } else {
                alert("Something went wrong: " + response.message);
              }
            }
          },
          error: function (xhr, status, error) {
            console.error("[CFP] Form Submission Error:", error);
            console.log("[CFP] Response Text:", xhr.responseText);
            
            if (typeof Swal !== "undefined") {
              Swal.fire({
                title: "CONNECTION ERROR",
                text: "An error occurred. Please try again later.",
                icon: "error",
                confirmButtonColor: "#000",
              });
            } else {
              alert("An error occurred. Please try again later.");
            }
          },
          complete: function () {
            // Restore button state
            $submitBtn.prop("disabled", false).text(originalBtnText);
          },
        });
      } else {
        console.log("[CFP] Form Validation Failed");
      }
    });
  });
})(jQuery);
