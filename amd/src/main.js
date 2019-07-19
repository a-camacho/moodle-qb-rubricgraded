/*
 * @copyright  2019 André Camacho
 */

define(['jquery'], function($) {

    /* TODO: We should maybe calculate filling JSON only once, when clicking on save button */
    /* TODO: Should max points be getted from php function with rubric id ? */

    return {
        init: function( maxmark, elementname, maxpoints ) {

            //eslint-disable-next-line
            var escapedelementname = elementname.replace("\:", "\\\:");
            //eslint-disable-next-line
            var escrubfillingname = escapedelementname.replace("-rubric", "-rubfilling");

            // Calculate scores at document.ready
            $( document ).ready(function() {
                var maxPoints = maxpoints;
                var totalPoints = calculatePoints();

                $("#totalPoints").html( totalPoints + '/' + maxPoints );
                $("#totalScoreDecimal").html( calculateDecimalTotal( totalPoints, maxPoints ) );
            });

            // Calculate scores and generate JSON at every input.change
            $('input[name^="'+escapedelementname+'"]').change(function () {
                if (this.checked) {
                    var totalPoints = calculatePoints();
                    var maxPoints = maxpoints;
                    var totalMark = calculateDecimalTotal( totalPoints, maxPoints );
                    $("#totalPoints").html( totalPoints + '/' + maxPoints );
                    $("#totalScoreDecimal").html( totalMark );
                    $("div#totalMark input").first().val( totalMark );
                    $('#' + escrubfillingname).val( generateFillingJSON() );
                }
            });

            // Generate JSON at every textarea.change
            $('textarea[name^="'+escapedelementname+'"]').change(function () {
                $('#' + escrubfillingname).val( generateFillingJSON() );
            });

            var generateFillingJSON = function() {
                var rubric_filling = [];
                $("#rubric-" + escapedelementname + " input:checked").each(function() {
                    var string = $(this).attr('id');
                    var criterion_value = string.match(/criteria-([^-]+)/)[1];
                    var level_value = string.match(/levels-([^-]+)/)[1];
                    var remark = $("#" + escapedelementname + "-criteria-" + criterion_value + "-remark").val();

                    var criterion = {};
                    criterion.criterion = criterion_value;
                    criterion.level = level_value;
                    criterion.remark = remark;

                    rubric_filling.push(criterion);
                });
                return JSON.stringify(rubric_filling);
            };

            var calculatePoints = function() {
                var pointsArray = [];
                var total = 0;

                $("#rubric-" + escapedelementname + " input:checked").each(function() {

                    // Push checkbox id into array
                    var checkbox_id = $(this).attr('id');

                    // Push checkbox_score into array
                    var checkbox_points_id = checkbox_id.slice(0,-11);
                    var automaticid = "#"+checkbox_points_id+"-score";
                    //eslint-disable-next-line
                    var escapedautomaticid = automaticid.replace("\:", "\\\:");

                    // Check if score div tag exists
                    var scoreid = document.getElementById(escapedautomaticid);
                    var checkbox_points = '';

                    // If it exists, get score from there
                    if (typeof(scoreid) != 'undefined' && scoreid != null) {

                        checkbox_points = $(escapedautomaticid).text();
                        checkbox_points = Number(checkbox_points);
                        pointsArray.push(checkbox_points);

                    // If it doesn't exist, get score label-aria
                    } else {

                        var element_id = "#"+checkbox_points_id;

                        //eslint-disable-next-line
                        element_id = element_id.replace("\:", "\\\:");

                        var score_string = $(element_id);
                        score_string = score_string.attr("aria-label");
                        score_string = score_string.split(", ");
                        score_string = score_string.pop();
                        score_string = score_string.split(" ");
                        score_string = score_string[0];

                        checkbox_points = Number(score_string);
                        pointsArray.push(checkbox_points);

                    }

                });

                if(pointsArray.length > 0){
                    for (var i = 0; i < pointsArray.length; i++) {
                        total += pointsArray[i] << 0;
                    }
                }

                return total;
            };

            var calculateDecimalTotal = function( totalPoints, maxPoints ) {
                var totalRounded;
                var totalDecimal;
                var maximumMark = maxmark;

                if (totalPoints > 0){
                    /* TODO : Is that the right way to calculate decimal total */
                    totalDecimal = eval(totalPoints/maxPoints);
                    // console.log('total is ' + totalPoints + '/' + maxPoints + '. Decimal value is = ' + totalDecimal);
                } else {
                    totalDecimal = 0;
                }

                // Weight totalDecimal against maxGrade of this question
                totalRounded = totalDecimal * maximumMark;
                totalRounded = totalRounded.toFixed(2);

                return totalRounded;
            }
        }
    };
});