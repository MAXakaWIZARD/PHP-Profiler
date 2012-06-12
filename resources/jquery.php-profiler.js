(function($) {
    var profiler_details = false;
    var height_toggle = false;
    var selected_log_type = null;

    $(document).ready(function() {

        //move console to the end ot body children
        $('#profiler-container').appendTo($('body').get(0));

        $('#profiler-container .query-profile h4').click(function() {
            if ($('table', $(this).parent()).is(':hidden')) {
                $(this).html('&#187; Hide Query Profile');
            } else {
                $(this).html('&#187; Show Query Profile');
            }

            $('table', $(this).parent()).toggle();
        });

        $('#profiler-container .detailsToggle').click(function() {
            $('#profiler-container').toggleClass('hideDetails', profiler_details);
            profiler_details = !profiler_details;

            if (profiler_details) {
                $('#profiler .tab.active').click();
            } else {
                $('.profiler-box').hide();
            }

            return false;
        });

        $('#profiler-container .heightToggle').click(function() {
            height_toggle = !height_toggle;

            $('.profiler-box').each(function() {
                $(this).css('height', (height_toggle ? '500px' : '200px'));
            });
        });

        $('#profiler .tab').click(function() {
            $('#profiler .tab').removeClass('active');
            $(this).addClass('active');

            $('.profiler-box').hide();
            $('#profiler-' + $(this).attr('id')).show();

            if (!profiler_details) {
                profiler_details = true;
                $('#profiler-container').removeClass('hideDetails');
            }
        });

        $('#profiler-console .side td').each(function() {
            var log_type = $(this).attr('id').split('-')[1];
            var log_count = $('var', $(this)).html();

            if (log_count == 0) {
                return;
            }

            $(this).click(function() {
                $('#profiler-console .main tr').each(function() {
                    var row_type = $(this).attr('class').split('-')[1];

                    if (row_type == log_type || selected_log_type == log_type) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                $('#profiler-console .side td').removeClass('selected');

                if (selected_log_type == log_type) {
                    selected_log_type = null;
                } else {
                    selected_log_type = log_type;
                    $(this).addClass('selected');
                }
            });
        });
    });
})(jQuery);