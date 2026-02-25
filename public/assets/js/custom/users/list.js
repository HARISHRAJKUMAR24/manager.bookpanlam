$(document).ready(function () {
  var table = $("#kt_table_users").DataTable({
    processing: true,
    serverSide: true,
    pageLength: 10,
    ordering: false,
    ajax: {
      url: `${BASE_URL}ajax/users/list.php`,
      type: "POST",
      data: function (d) {
        d.search = $("#searchFilter").val();
        d.isSuspended = $("#suspendedFilter").val();
        
        // Handle plan filter - send special value for "No Plan"
        let planValue = $("#planFilter").val();
        if (planValue === "no_plan") {
          d.planId = "no_plan"; // Send NULL as string to indicate no plan
        } else {
          d.planId = planValue;
        }
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