$("#addForm").on("submit", function (e) {
  e.preventDefault();

  const element = this;
  const action = $(this).attr("action");
  const formData = new FormData(this);

  $.ajax({
    url: action,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (data) {
      const response = JSON.parse(data);
      toastr[response.type](response.msg);

      response.type === "success" && element.reset();
    },
  });
});
