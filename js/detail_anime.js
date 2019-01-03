$(document).ready(function() {
    // CSV に対する詳細を見せる・隠すときの矢印
    $("#collapse-csv").on('shown.bs.collapse', function() {
        $("#heading-csv > span").removeClass("fas fa-chevron-down").addClass("fas fa-chevron-up");
    });
    $("#collapse-csv").on('hidden.bs.collapse', function() {
        $("#heading-csv > span").removeClass("fas fa-chevron-up").addClass("fas fa-chevron-down");
    });

    // テーブルの編集に対する詳細を見せる・隠すときの矢印
    $("#collapse-edit-table").on('shown.bs.collapse', function() {
        $("#heading-edit-table > span").removeClass("fas fa-chevron-down").addClass("fas fa-chevron-up");
    });
    $("#collapse-edit-table").on('hidden.bs.collapse', function() {
        $("#heading-edit-table > span").removeClass("fas fa-chevron-up").addClass("fas fa-chevron-down");
    });
});
