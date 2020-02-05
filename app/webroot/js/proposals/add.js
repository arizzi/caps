var exams = undefined;
var groups = undefined;
var curricula = undefined;
var cv_per_year = undefined;

var compulsoryExams = undefined;
var compulsoryGroups = undefined;
var freeChoiceExams = undefined;

var lastExamAdded = 0;
var lastFreeChoiceExamAdded = 0;

var load_data_promise = Promise.all([
    $.get(examsURL, (response) => {
        exams = response["exams"];
    }),
    $.get(groupsURL, function (response) {
        groups = response["groups"];
    }),
    $.get(curriculaURL, function (response) {
        curricula = response['curricula'];
    })
]);

function on_curriculum_selected() {
        var curriculum = $("#curriculum-0-curriculum-id option:selected").text();
        var curriculumId = $("#curriculum-0-curriculum-id").val();
        if (curriculumId === "")
            return;

        var year = $('#academicYearSelect').val();
        var cv = cv_per_year[year][curriculumId];
        var degree = cv['degree']['name'];

        var baseHTML = undefined;
        if (degree.toLowerCase().includes('triennale')) {
            baseHTML = "<hr><h3>Primo anno: <span></span>/60</h3><nav id=\"nav-year-1\"></nav><hr><ul></ul><hr><h3>Secondo anno: <span></span>/60</h3><nav id=\"nav-year-2\"></nav><hr><ul></ul><hr><h3>Terzo anno: <span></span>/60</h3><nav id=\"nav-year-3\"></nav><hr><ul></ul>";
        } else {
            baseHTML = "<hr><h3>Primo anno: <span></span>/60</h3><nav id=\"nav-year-1\"></nav><hr><ul></ul><hr><h3>Secondo anno: <span></span>/60</h3><nav id=\"nav-year-2\"></nav><hr><ul></ul>";
        }

        $("#proposalForm").hide();

        $.get(curriculumURL + cv['id'] + ".json",
            function (response) {
                compulsoryExams = response["compulsory_exams"];
                compulsoryGroups = response["compulsory_groups"];
                freeChoiceExams = response["free_choice_exams"];

                $("#proposalForm").html(baseHTML);

                addCompulsoryExams();
                addCompulsoryGroups();
                addFreeChoiceExams();
                addButtons();
                addCounters();
                addDeleteButtons();
                addKonamiCode();

                $("#proposalForm").slideDown();
            });
    }

    var addCompulsoryExams = function () {
        for (var i = 0; i < compulsoryExams.length; i++) {
            var compulsoryExam = compulsoryExams[i];
            var examId = compulsoryExam["exam_id"];
            var year = compulsoryExam["year"];

            var exam;
            // XXX(jacquerie): I apologize.
            for (var j = 0; j < exams.length; j++) {
                if (exams[j]["id"] == examId) {
                    exam = exams[j];
                }
            }

            var selector = "#proposalForm > ul:nth-of-type(" + year + ")";
            var examHTML = "<select name=data[ChosenExam][" + i + "][exam_id] class=exam><option value=" + examId + ">" + exam["code"] + " — " + exam["name"] + " — " + exam["sector"]  + "</option></select>";
            var creditsHTML = "<select name=data[ChosenExam][" + i + "][credits] class=credits>";
            if (exam["sector"] === "PROFIN") {
                creditsHTML += "<option value=" + exam["credits"] + ">" + exam["credits"] + "</option>";
            } else {
                for (var j = exam["credits"]; j > 0; j--) {
                    creditsHTML += "<option value=" + j + ">" + j + "</option>";
                }
            }
            creditsHTML += "</select>";

            // Add an hidden field with the ID of this compulsoryExam
            var compulsory_exam_id = "<input type=hidden name=data[ChosenExam][" + i + "][compulsory_exam_id] value=" + compulsoryExam["id"] + ">";

            // Store the year for this exam as well
            var year_input = "<input type=hidden name=data[ChosenExam][" + i + "][chosen_year] value=" + year + ">";

            $(selector).append("<li>" + examHTML + creditsHTML + compulsory_exam_id + year_input + "</li>");
        }
    };

    var addCompulsoryGroups = function () {
        for (var i = 0; i < compulsoryGroups.length; i++) {
            var compulsoryGroup = compulsoryGroups[i];
            var groupId = compulsoryGroup["group_id"];
            var year = compulsoryGroup["year"];

            var group;
            // XXX(jacquerie): I apologize.
            for (var j = 0; j < groups.length; j++) {
                if (groups[j]["id"] == groupId) {
                    group = groups[j];
                }
            }

            var groupExams = group["exams"];
            var groupName = group["name"];

            var selector = "#proposalForm > ul:nth-of-type(" + year + ")";

            var groupHTML = "<option value='' disabled selected>Un esame a scelta nel gruppo " + groupName + "</option>";
            var examsHTML = "";
            for (var j = 0; j < groupExams.length; j++) {
                examsHTML += "<option value=" + groupExams[j]["id"] + ">" + groupExams[j]["code"] + " — " + groupExams[j]["name"] + " — " + groupExams[j]["sector"] + "</option>";
            }

            // Add an hidden field with the ID of this compulsoryGroup
            var compulsory_group_id = "<input type=hidden name=data[ChosenExam][" + (i + compulsoryExams.length) + "][compulsory_group_id] value=" + compulsoryGroup["id"] + ">";

            // Store the year for this exam as well
            var year_input = "<input type=hidden name=data[ChosenExam][" + (i + compulsoryExams.length) + "][chosen_year] value=" + year + ">";

            $(selector).append("<li><select name=data[ChosenExam][" + (i + compulsoryExams.length) + "][exam_id] class=exam>" + groupHTML + examsHTML + compulsory_group_id + year_input + "</li></select>");
            $(selector + " li select:last").change({ i: i }, function (e) {
                var examId = $(this).val();
                var exam;
                // XXX(jacquerie): I apologize.
                for (var j = 0; j < exams.length; j++) {
                    if (exams[j]["id"] == examId) {
                        exam = exams[j];
                    }
                }

                var credits = exam["credits"];
                var creditsHTML = "<select name=data[ChosenExam][" + (e.data.i + compulsoryExams.length)  + "][credits] class=credits>";
                for (var j = credits; j > 0; j--) {
                    creditsHTML += "<option value=" + j + ">" + j + "</option>";
                }
                creditsHTML += "</select>";

                $(this).next("select").remove();
                $(this).after(creditsHTML);
                updateCounters();
            });
        }
    };

    var addFreeChoiceExams = function () {
        for (var i = 0; i < freeChoiceExams.length; i++) {
            var freeChoiceExam = freeChoiceExams[i];
            var year = freeChoiceExam["year"];

            var selector = "#proposalForm > ul:nth-of-type(" + year + ")";
            var selectHTML = "<select name=data[ChosenExam][" + (i + compulsoryGroups.length + compulsoryExams.length) + "][exam_id] class=exam><option selected disabled>Un esame di Matematica a scelta</option>";
            for (var j = 0; j < exams.length; j++) {
                var exam = exams[j];
                selectHTML += "<option value=" + exam["id"] + ">" + exam["code"] + " — " + exam["name"] + " — " + exam["sector"] + "</option>";
            }
            selectHTML += "</select>";
            var $selectHTML = $(selectHTML);
            var deleteHTML = "<a class=delete></a>";

            $selectHTML.change({ i: i }, function (e) {
                var examId = $(this).val();
                var exam;
                // XXX(jacquerie): I apologize.
                for (var j = 0; j < exams.length; j++) {
                    if (exams[j]["id"] == examId) {
                        exam = exams[j];
                    }
                }

                var creditsHTML = "<select name=data[ChosenExam][" + (e.data.i + compulsoryGroups.length + compulsoryExams.length) + "][credits] class=credits>";
                if (exam["sector"] === "PROFIN") {
                        creditsHTML += "<option value=" + exam["credits"] + ">" + exam["credits"] + "</option>";
                } else {
                    for (var j = exam["credits"]; j > 0; j--) {
                        creditsHTML += "<option value=" + j + ">" + j + "</option>";
                    }
                }
                creditsHTML += "</select>";

                $(this).next("select").remove();
                $(this).after(creditsHTML);
                updateCounters();
            });

            // Add an hidden field with the ID of this free choice exam
            var free_choice_exam_id = "<input type=hidden name=data[ChosenExam][" + (i + compulsoryGroups.length + compulsoryExams.length) + "][free_choice_exam_id] value=" + freeChoiceExam["id"] + ">";

            // Store the year for this exam as well
            var year_input = "<input type=hidden name=data[ChosenExam][" + (i + compulsoryGroups.length + compulsoryExams.length) + "][chosen_year] value=" + year + ">";

            $(selector).append($("<li>").append($selectHTML).append(free_choice_exam_id).append(year_input).append(deleteHTML));
        }
    };

    var addButtons = function () {
        var buttonsHTML = "<ul class=actions><li><a href=# class=newMathematicsExam>Aggiungi esame di Matematica</li><li><a href=# class=newFreeChoiceExam>Aggiungi esame a scelta libera</a></li></ul>";
        $("#proposalForm nav").append(buttonsHTML);

        $(".newMathematicsExam").click(function (e) {
            e.preventDefault();

            var i = compulsoryExams.length + compulsoryGroups.length + freeChoiceExams.length + lastExamAdded;
            var selectHTML = "<select name=data[ChosenExam][" + i + "][exam_id] class=exam><option selected disabled>Un esame di Matematica a scelta</option>";
            for (var j = 0; j < exams.length; j++) {
                var exam = exams[j];
                selectHTML += "<option value=" + exam["id"] + ">" + exam["code"] + " — " + exam["name"] + " — " + exam["sector"] + "</option>";
            }
            selectHTML += "</select>";
            var $selectHTML = $(selectHTML);

            $selectHTML.change({ i: i }, function (e) {
                var examId = $(this).val();
                var exam;
                // XXX(jacquerie): I apologize.
                for (var j = 0; j < exams.length; j++) {
                    if (exams[j]["id"] == examId) {
                        exam = exams[j];
                    }
                }

                var creditsHTML = "<select name=data[ChosenExam][" + e.data.i + "][credits] class=credits>";
                if (exam["sector"] === "PROFIN") {
                        creditsHTML += "<option value=" + exam["credits"] + ">" + exam["credits"] + "</option>";
                } else {
                    for (var j = exam["credits"]; j > 0; j--) {
                        creditsHTML += "<option value=" + j + ">" + j + "</option>";
                    }
                }
                creditsHTML += "</select>";

                $(this).next("select").remove();
                $(this).after(creditsHTML);
                updateCounters();
            });

            // This ID is used in the closure to set up the correct year.
            let thisExamID = i;

            lastExamAdded++;

            var deleteHTML = "<a href=# class=delete></a>";

            $(this).closest("nav").each((j, nav) => {
                // Read the year from the ID
                year = $(nav).attr('id').split('-')[2];

                var year_input = "<input type=hidden name=data[ChosenExam][" +
                    thisExamID + "][chosen_year] value=" + year + ">";

                deleteHTML = year_input + deleteHTML;

                $(nav).next("hr").next("ul").append($("<li>").append($selectHTML).append(deleteHTML));
            });

        });

        $(".newFreeChoiceExam").click(function (e) {
            e.preventDefault();

            var inputHTML = "<input name=data[ChosenFreeChoiceExam][" + lastFreeChoiceExamAdded + "][name] type=text placeholder='Un esame a scelta libera' class=exam></input>";
            var creditsHTML = "<input name=data[ChosenFreeChoiceExam][" + lastFreeChoiceExamAdded + "][credits] type=number min=1 class=credits></input>";
            var deleteHTML = "<a href=# class=delete></a>";

            // This ID is used in the closure to set up the correct year.
            let thisExamID = lastFreeChoiceExamAdded;

            $(this).closest("nav").each ((j, nav) => {
                // Read the year from the ID
                year = $(nav).attr('id').split('-')[2];

                var year_input = "<input type=hidden name=data[ChosenFreeChoiceExam][" +
                    thisExamID + "][chosen_year] value=" + year + ">";

                inputHTML = inputHTML + year_input;

                $(nav).next("hr").next("ul").append("<li>" + inputHTML + creditsHTML + deleteHTML + "</li>");
            });

            lastFreeChoiceExamAdded++;
        });
    };

    var addCounters = function () {
        $("#proposalForm").on("change", ".credits", function () {
            updateCounters();
        });

        updateCounters();
    };

    var updateCounters = function () {
        $("h3 span").each(updateCounter);

        if ($(".creditsError").length == 0) {
            $("input[type=submit]").show();
        } else {
            $("input[type=submit]").hide();
        }
    };

    var updateCounter = function () {
        var result = 0;

        $(this).closest("h3").next("nav").next("hr").next("ul").each(function () {
            $(this).find(".credits").each(function () {
                var credits = parseInt($(this).val());
                result += isNaN(credits) ? 0 : credits;
            });
        });

        // Clean classes, so only one in creditsError or creditsWarning
        // is set at any time.
        $(this).removeClass("creditsError");
        $(this).removeClass("creditsWarning");

        $("#proposalWarning").hide();

        if (result < 60) {
            $(this).addClass("creditsError");
        } else if (result > 60) {
            $(this).addClass("creditsWarning");

            $("#proposalWarning").html(
                "<strong>Attenzione</strong>: il numero di crediti per anno è "
                + "superiore a 60. È comunque possibile chiudere il piano "
                + "di studi, ma si consiglia di ricontrollarlo attentamente "
                + "prima di procedere.");
            $("#proposalWarning").show();
        }

        $(this).html(result);
    };

    var addDeleteButtons = function () {
        $("#proposalForm").on("click", ".delete", function (e) {
            e.preventDefault();
            $(this).parent().remove();
            updateCounters();
        });
    };

    var addKonamiCode = function () {
        var status = 0;

        $("body").keydown(function (e) {
            if ((e.which === 38 && (status === 0 || status === 1))
            ||  (e.which === 40 && (status === 2 || status === 3))
            ||  (e.which === 37 && (status === 4 || status === 6))
            ||  (e.which === 39 && (status === 5 || status === 7))
            ||  (e.which === 66 && status === 8)) {
                status++;
            } else if (e.which === 65 && status === 9) {
                $("#content").append("<div class=nyanCat></div>");
                $(".nyanCat").fadeOut({
                    complete: function () { $(this).remove(); },
                    duration: 2500
                });
                status = 0;
            } else {
                status = 0;
            }
        });
    };

function load_proposal(proposal) {
    console.log("Loading proposal with ID = " + proposal["id"]);

    // Set the correct Curriculum
    $("#curriculum-0-curriculum-id").val(proposal["curriculum"][0]["id"]);
    $("#completeForm").show();
    on_curriculum_selected();
}

function addAcademicYearInput() {
    console.log("Adding selection of academic year");

    // Groups CV by academic_years
    cv_per_year = [];

    for (j = 0; j < curricula.length; j++) {
        let year = curricula[j].academic_year;
        if (year in cv_per_year) {
            cv_per_year[year].push(curricula[j]);
        }
        else {
            cv_per_year[year] = [ curricula[j] ];
        }
    }

    // Add the options
    years = cv_per_year.keys();
    var options_html = "<option value=text>Scegliere l'anno di immatricolazione</option>";
    cv_per_year.forEach(function (cv, year) {
        var opt = "<option value=" + year + ">" + year + "/" + (year + 1) + "</option>";
        options_html = options_html + opt;
    });

    var year_selection_html = $(
        "<label>Anno Accademico</label>" +
        "<select name=academic_year id='academicYearSelect'>" + options_html + "</select>"
    );
    $('#curriculum-select').append(year_selection_html);
    $("#academicYearSelect").change(on_academic_year_selected);
}

function on_academic_year_selected() {
    var year = $('#academicYearSelect').val();

    // Clear any previous form that might have been loaded
    $('#curriculum-0-curriculum-id').remove();
    $('#curriculum-0-curriculum-id-label').remove();
    $('#proposalForm').hide();

    // Create the form for the curriculum choice
    var options = "<option value=text>Selezionare il curriculum</option>";
    cv_per_year[year].forEach(function (cv, j) {
        options = options +
            "<option value=" + j + ">" +
            cv['degree']['name'] + " — Curriculum " + cv['name'] + "</option>";
    });

    var curriculum_select_html =
        "<label id='curriculum-0-curriculum-id-label'>Curriculum</label>" +
        "<select name=Curriculum[0][curriculum_id] id=curriculum-0-curriculum-id>" +
        options +
        "</select>";

    $('#curriculum-select').append(curriculum_select_html);
    $('#curriculum-0-curriculum-id').change(on_curriculum_selected);
}

$(document).ready(function () {


    $("input[type=submit]").hide();


    // Only show the form to select the plan if there is not ID, otherwise it
    // will be loaded in the background and shown afterwards.
    load_data_promise.then(function () {
        if (proposal["id"] !== undefined) {
            load_proposal(proposal);
        }
        else {
            addAcademicYearInput();
            $("#completeForm").show();
        }
    }, function (err) {
        console.log("Error loading data");
    });
});
