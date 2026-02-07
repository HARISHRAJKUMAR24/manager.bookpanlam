$(document).ready(function () {
  var table = $("#kt_table_users").DataTable({
    processing: true,
    serverSide: true, // Enable server-side processing
    pageLength: 10, // Default number of records to display
    ordering: false,
    ajax: {
      url: `${BASE_URL}ajax/users/list.php`,
      type: "POST",
      data: function (d) {
        d.search = $("#searchFilter").val();

        d.isSuspended = $("#suspendedFilter").val();
        d.planId = $("#planFilter").val();
      },
    },
    columns: [
      { data: "id" },
      { data: "user_id" },
      { data: "user" },
      { data: "site" },
      { data: "plan" },
      { data: "expires_on" },
      { data: "is_suspended" },
      { data: "actions" },
    ],
  });

  $("#searchFilter, #applyFilter").on("keyup change click", function () {
    table.ajax.reload();
  });

  $("#resetFilter").on("click", function () {
    $("#suspendedFilter").val("");
    $("#planFilter").val("");
    table.ajax.reload();
  });
});
