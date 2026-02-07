$(document).ready(function () {
  var table = $("#kt_table_users").DataTable({
    processing: true,
    serverSide: true, // Enable server-side processing
    pageLength: 10, // Default number of records to display
    ordering: false,
    ajax: {
      url: `${BASE_URL}ajax/staffs/list.php`,
      type: "POST",
      data: function (d) {
        d.search = $("#searchFilter").val();
      },
    },
    columns: [
      { data: "id" },
      { data: "staff_id" },
      { data: "staff" },
      { data: "created_at" },
      { data: "actions" },
    ],
  });

  $("#searchFilter, #applyFilter").on("keyup change click", function () {
    table.ajax.reload();
  });

  $(document).on("click", ".deleteStaff", function () {
    const id = $(this).data("id");

    $.ajax({
      url: `${BASE_URL}ajax/staffs/delete.php`,
      type: "POST",
      data: { id },
      success: function () {
        table.ajax.reload();
      },
    });
  });
});
