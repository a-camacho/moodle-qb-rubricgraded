/*
 * @copyright  2019 André Camacho
 */

define(['jquery'], function($) {
    /* TODO : How to fix some Javascript variables so we can use them in each function without recreation */

    return {
        init: function( maxmark, elementname ) {

            var escapedelementname = elementname.replace("\:", "\\\:");
            var escrubfillingname = escapedelementname.replace("-rubric", "-rubfilling");

            $( document ).ready(function() {
                var maxPoints = getMaxPoints();
                var totalPoints = calculatePoints();

                $("#totalPoints").html( totalPoints + '/' + maxPoints );
                $("#totalScoreDecimal").html( calculateDecimalTotal( totalPoints, maxPoints ) );
            });

            /* Here Jquery variable needs to be escaped. TODO : Because ? */
            $('input[name^="'+escapedelementname+'"]').change(function () {

                if (this.checked) {

                    var totalPoints = calculatePoints();
                    var maxPoints = getMaxPoints();
                    var totalMark = calculateDecimalTotal( totalPoints, maxPoints );

                    $("#totalPoints").html( totalPoints + '/' + maxPoints );
                    $("#totalScoreDecimal").html( totalMark );
                    $("div#totalMark input").first().val( totalMark );

                    $('#' + escrubfillingname).val( generateFillingJSON() );

                }
            });

            var generateFillingJSON = function() {

                var rubric_filling = [];

                /* Here Jquery variable needs to be escaped. TODO : Because ? */
                $("#rubric-" + escapedelementname + " input:checked").each(function() {

                    string = $(this).attr('id');

                    var criterion_value = string.match(/criteria-([^-]+)/)[1];
                    // var criterion = parseInt(criterion, 10);
                    var level_value = string.match(/levels-([^-]+)/)[1];

                    var remark = $("#" + escapedelementname + "-criteria-" + criterion_value + "-remark").val();

                    // console.log("criterion is " + criterion + " and level is " + level);

                    var criterion = {};
                    criterion.criterion = criterion_value;
                    criterion.level = level_value;
                    criterion.remark = remark;

                    rubric_filling.push(criterion);

                });

                return JSON.stringify(rubric_filling);

            };

            /* TODO: Should max points be getted from php function with rubric id ? */
            var getMaxPoints = function() {

                var maxPointsArray = [];

                /* Here Jquery variable does not need to be escaped. TODO : Why ? */
                $('td.last[id^="' + elementname + '-criteria"]').each(function() {
                    var maxCriterionPoints = $(this).find("span.scorevalue").text();
                    maxPointsArray.push(Number(maxCriterionPoints));
                });

                if(maxPointsArray.length > 0){
                    var total = 0;
                    for (var i = 0; i < maxPointsArray.length; i++) {
                        total += maxPointsArray[i] << 0;
                    }
                    return total;
                } else {
                    return 0;
                }
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
                    var escapedautomaticid = automaticid.replace("\:", "\\\:");

                    var checkbox_points = $(escapedautomaticid).text();

                    checkbox_points = Number(checkbox_points);
                    pointsArray.push(checkbox_points);

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