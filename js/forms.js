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

    console.log("[CFP] Forms JS Version 1.2 Initializing");

    // Use a very broad selector to catch all forms and then filter
    $(document).on("submit", "form", function (e) {
      var $form = $(this);
      
      // Only process if it has our data attribute
      if ($form.attr('data-cfp-form') === undefined && $form.attr('id') !== 'application-form') {
        return; 
      }

      console.log("[CFP] Form submission detected from:", $form.attr('data-cfp-form') || $form.attr('id'));
      
      e.preventDefault();
      e.stopPropagation();

      var $submitBtn = $form.find('button[type="submit"]');
      var originalBtnText = $submitBtn.text();

      // Disable button and show loading state
      $submitBtn.prop("disabled", true).text("SENDING...");

      try {
        var formData = new FormData(this);
        var formTitle = $form.prevAll("h1, h2").first().text() || 
                        $form.parent().prevAll("h1, h2").first().text() || 
                        $form.closest('main, section').find('h1, h2').first().text() ||
                        "Website Form";
        
        formData.append("form_type", formTitle.trim());

        console.log("[CFP] Attempting AJAX request to includes/process-form.php");

        $.ajax({
          url: "includes/process-form.php",
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function (response) {
            console.log("[CFP] Success response:", response);
            if (response.status === "success") {
              if (typeof Swal !== "undefined") {
                Swal.fire({
                  title: "THANK YOU!",
                  text: response.message,
                  icon: "success",
                  confirmButtonColor: "#000"
                });
              } else {
                alert("Thank you! Your message has been sent.");
              }
              $form[0].reset();
            } else {
              throw new Error(response.message || "Server returned failure status");
            }
          },
          error: function (xhr, status, error) {
            console.error("[CFP] AJAX Error:", status, error);
            console.log("[CFP] Response Text:", xhr.responseText);
            if (typeof Swal !== "undefined") {
              Swal.fire({
                title: "SUBMISSION ERROR",
                text: "Could not send message. Please check your connection.",
                icon: "error",
                confirmButtonColor: "#000"
              });
            } else {
              alert("Error: Could not send message.");
            }
          },
          complete: function () {
            $submitBtn.prop("disabled", false).text(originalBtnText);
          }
        });
      } catch (err) {
        console.error("[CFP] Script Error:", err);
        $submitBtn.prop("disabled", false).text(originalBtnText);
        alert("An unexpected error occurred. Please try again.");
      }

      return false;
    });
  });
})(jQuery);
