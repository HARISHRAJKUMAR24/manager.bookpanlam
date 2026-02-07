$(document).ready(function () {
  var table = $("#kt_table").DataTable({
    processing: true,
    serverSide: true, // Enable server-side processing
    pageLength: 10, // Default number of records to display
    ordering: false,
    ajax: {
      url: `${BASE_URL}ajax/discounts/list.php`,
      type: "POST",
      data: function (d) {
        d.search = $("#searchFilter").val();
      },
    },
    columns: [
      { data: "id" },
      { data: "code" },
      { data: "type" },
      { data: "discount" },
      { data: "created_at" },
      { data: "actions" },
    ],
  });

  $("#searchFilter, #applyFilter").on("keyup change click", function () {
    table.ajax.reload();
  });

  $(document).on("click", ".deleteCode", function () {
    const id = $(this).data("id");

    $.ajax({
      url: `${BASE_URL}ajax/discounts/delete.php`,
      type: "POST",
      data: { id },
      success: function () {
        table.ajax.reload();
      },
    });
  });
});
