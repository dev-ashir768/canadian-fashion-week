console.log("[CFP] Forms JS External File Loaded");

(function ($) {
  $(function () {
    console.log("[CFP] Forms JS Ready Block Execution");

    // Use a specific selector for the forms we want to handle
    $(document).on("submit", "form[data-cfp-form], #application-form", function (e) {
      var $form = $(this);
      
      console.log("[CFP] Submit Intercepted for:", $form.attr('data-cfp-form') || $form.attr('id'));
      
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

        console.log("[CFP] Sending AJAX to includes/process-form.php");

        $.ajax({
          url: "includes/process-form.php",
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function (response) {
            console.log("[CFP] Server Response:", response);
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
              throw new Error(response.message || "Server Error");
            }
          },
          error: function (xhr, status, error) {
            console.error("[CFP] AJAX Failure:", status, error);
            console.log("[CFP] Raw Response:", xhr.responseText);
            if (typeof Swal !== "undefined") {
              Swal.fire({
                title: "SUBMISSION ERROR",
                text: "Could not send message. Details: " + error,
                icon: "error",
                confirmButtonColor: "#000"
              });
            } else {
              alert("Error: " + error);
            }
          },
          complete: function () {
            $submitBtn.prop("disabled", false).text(originalBtnText);
          }
        });
      } catch (err) {
        console.error("[CFP] Execution Error:", err);
        $submitBtn.prop("disabled", false).text(originalBtnText);
        alert("Script error: " + err.message);
      }

      return false;
    });
  });
})(jQuery);
