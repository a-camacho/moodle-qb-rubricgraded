/*
 * @copyright  2019 André Camacho
 */

define(['jquery'], function($) {
    /* TODO : How to fix some Javascript variables so we can use them in each function without recreation */

    return {
        init: function( maxmark ) {

            $( document ).ready(function() {
                var maxPoints = getMaxPoints();
                var totalPoints = calculatePoints();

                $("#totalPoints").html( totalPoints + '/' + maxPoints );
                $("#totalScoreDecimal").html( calculateDecimalTotal( totalPoints, maxPoints ) );
            });

            $('input[name^="mycustomname"]').change(function () {
                if (this.checked) {

                    var totalPoints = calculatePoints();
                    var maxPoints = getMaxPoints();
                    var totalMark = calculateDecimalTotal( totalPoints, maxPoints );

                    $("#totalPoints").html( totalPoints + '/' + maxPoints );
                    $("#totalScoreDecimal").html( totalMark );
                    $("div#totalMark input").first().val( totalMark );

                }
            });

            /* TODO: Should max points be fixed by PHP once ? Probably. */
            var getMaxPoints = function() {

                var maxPointsArray = [];

                $('td.last[id^="mycustomname-criteria"]').each(function() {
                    maxPointsArray.push(Number($('#'+$(this).attr('id')+'-score').text()));
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

                // var chkArray = [];
                var pointsArray = [];
                var total = 0;

                $("#rubric-mycustomname input:checked").each(function() {

                    // Push checkbox id into array
                    var checkbox_id = $(this).attr('id');
                    // chkArray.push(checkbox_id);

                    // Push checkbox_score into array
                    var checkbox_points_id = checkbox_id.slice(0,-11)
                    var checkbox_points = $("#"+checkbox_points_id+"-score").text();

                    pointsArray.push(Number(checkbox_points));

                });

                if(pointsArray.length > 0){
                    for (var i = 0; i < pointsArray.length; i++) {
                        total += pointsArray[i] << 0;
                    }
                    return total;
                }

            };

            var calculateDecimalTotal = function( totalPoints, maxPoints ) {

                var totalRounded;
                var totalDecimal;
                var maximumMark = maxmark;

                if (totalPoints > 0){
                    /* TODO : Is that the right way to calculate decimal total */
                    totalDecimal = eval(totalPoints/maxPoints);
                    console.log('total is ' + totalPoints + '/' + maxPoints + '. Decimal value is = ' + totalDecimal);
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