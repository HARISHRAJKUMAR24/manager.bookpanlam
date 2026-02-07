$(document).on("click", "#toggleIsDisabled", function () {
  const id = $(this).data("id");
  const element = $(this);

  $.ajax({
    url: `${BASE_URL}ajax/subscription-plans/list.php`,
    type: "POST",
    data: {
      planId: id,
    },
    success: function (data) {
      if (element.text() === "Disabled") {
        element.text("Enabled");

        element.removeClass("btn-danger");
        element.addClass("btn-success");
      } else {
        element.text("Disabled");

        element.removeClass("btn-success");
        element.addClass("btn-danger");
      }

      Swal.fire({
        text: "Action applied successfully!",
        icon: "success",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: {
          confirmButton: "btn btn-primary",
        },
      });
    },
  });
});
