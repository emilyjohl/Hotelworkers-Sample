// Credit David Walsh (https://davidwalsh.name/javascript-debounce-function)
// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce(func, wait, immediate) {
  var timeout;

  return function executedFunction() {
    var context = this;
    var args = arguments;

    var later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };

    var callNow = immediate && !timeout;

    clearTimeout(timeout);

    timeout = setTimeout(later, wait);

    if (callNow) func.apply(context, args);
  };
};

window.pluralize = function (val) {
    if(val == 'Houseman') {
        return 'Housemen';
    } else if(val == 'Room Inspector/Housekeeping Supervisor') {
        return 'Room Inspectors/Housekeeping Supervisors';
    } else if(val == 'Security') {
        return 'Security Officers';
    } else {
        return val + 's';
    }
}

function slugify(text) {
  return text.toString().toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-')
    .replace(/^-+/, '')
    .replace(/-+$/, '');
}


$(document).ready(function () {
    if($('.union-comparison-survey').length > 0) {
        init_nu_survey();
    }
});

function init_nu_survey() {
    var nu_survey = $('#union-comparison-survey');

    nu_survey.detach();
    $('body').append(nu_survey);

    var sections;
    var applicable_sections_count = 0;
    var slides = nu_survey.find('#slides');
    var actions = nu_survey.find('#actions');
    var responses = {
        mergeVars: {
            masterZone: '',
            wageRate: '',
            wageItemRates: '',
            classification: ''
        }
    };
    var previous_classification = '', previous_zip = '';
    var scores = {};
    var question_slide_template = Handlebars.compile($("#question-slide-template").html());
    var comparison_slide_template = Handlebars.compile($("#comparison-slide-template").html());
    var nu_survey_template = Handlebars.compile($("#nu-survey-template").html());
    var union_score;
    var non_union_score;
    var fe_key = null;
    var h_fe_key = null;
    var s_fe_key = null;
    var fid = 14;
    var survey_data = {};

    $.getJSON(localeUrlPath + '/non-union-survey/get-data.json', function(results) {
        survey_data['masterZones'] = results['masterZones'];
        survey_data['classifications'] = results['classifications'];
        survey_data['zips'] = results['zips'];
        survey_data['wageRates'] = results['wageRates'];
        survey_data['wageItemRates'] = results['wageItemRates'];
    });

    function calc_merge_vars() {
        if(responses.zip in survey_data['zips']) {
            responses.mergeVars.masterZone = survey_data['masterZones'][survey_data['zips'][responses.zip]['masterZone']];
            responses.mergeVars.county = survey_data['zips'][responses.zip]['county'];
            responses.mergeVars.classification = survey_data['classifications'][responses.classification];

            if(responses.mergeVars.masterZone) {
                responses.mergeVars.wageRate = survey_data['wageRates'][responses.mergeVars.masterZone['name']][responses.classification];
                responses.mergeVars.wageItemRates = survey_data['wageItemRates'][responses.mergeVars.masterZone['name']];

                if(responses.mergeVars.classification) {
                    if(responses.mergeVars.masterZone.contractName == 'IWA') {
                        responses.mergeVars.classification.tipped = responses.mergeVars.classification.tippedIwa;
                    } else if(responses.mergeVars.masterZone.contractName == 'GRIWA') {
                        responses.mergeVars.classification.tipped = responses.mergeVars.classification.tippedGriwa;
                    }
                }
            }
        }
    }

    function apply_merge_vars() {
        if(!responses.mergeVars.masterZone) {
            return;
        }

        for(var i=1; i < sections.length; i++) {
            var section_evaluated = JSON.parse(JSON.stringify(sections[i]));

            if(section_evaluated['label_' + responses.mergeVars.masterZone.contractName]) {
                section_evaluated['label'] = eval('`' + section_evaluated['label_' + responses.mergeVars.masterZone.contractName] + '`');
            } else if(section_evaluated['label_' + responses.mergeVars.masterZone.contractName]) {
                section_evaluated['label'] = eval('`' + section_evaluated['label_' + responses.mergeVars.masterZone.contractName] + '`');
            } else if(section_evaluated['label']) {
                section_evaluated['label'] = eval('`' + section_evaluated['label'] + '`');
            }

            if(section_evaluated['body_' + responses.mergeVars.masterZone.contractName]) {
                section_evaluated['body'] = eval('`' + section_evaluated['body_' + responses.mergeVars.masterZone.contractName] + '`');
            } else if(section_evaluated['body_' + responses.mergeVars.masterZone.contractName]) {
                section_evaluated['body'] = eval('`' + section_evaluated['body_' + responses.mergeVars.masterZone.contractName] + '`');
            } else if(section_evaluated['body']) {
                section_evaluated['body'] = eval('`' + section_evaluated['body'] + '`');
            }

            for(var k in section_evaluated.blocks) {
                if(section_evaluated['blocks'][k]['label_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['blocks'][k]['label'] = eval('`' + section_evaluated['blocks'][k]['label_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['blocks'][k]['label_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['blocks'][k]['label'] = eval('`' + section_evaluated['blocks'][k]['label_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['blocks'][k]['label']) {
                    section_evaluated['blocks'][k]['label'] = eval('`' + section_evaluated['blocks'][k]['label'] + '`');
                }

                if(section_evaluated['blocks'][k]['body_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['blocks'][k]['body'] = eval('`' + section_evaluated['blocks'][k]['body_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['blocks'][k]['body_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['blocks'][k]['body'] = eval('`' + section_evaluated['blocks'][k]['body_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['blocks'][k]['body']) {
                    section_evaluated['blocks'][k]['body'] = eval('`' + section_evaluated['blocks'][k]['body'] + '`');
                }

                if(section_evaluated['blocks'][k]['body_2_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['blocks'][k]['body_2'] = eval('`' + section_evaluated['blocks'][k]['body_2_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['blocks'][k]['body_2_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['blocks'][k]['body_2'] = eval('`' + section_evaluated['blocks'][k]['body_2_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['blocks'][k]['body_2']) {
                    section_evaluated['blocks'][k]['body_2'] = eval('`' + section_evaluated['blocks'][k]['body_2'] + '`');
                }
            }

            section_evaluated.complete = false;

            if(!sections[i].complete) {
                $('.question-slide[data-section-index="' + i + '"] .slide-inner').html(question_slide_template(section_evaluated));
                nu_survey.find('form').off('submit').on('submit', next_slide);

                if(sections[i].comparable) {
                    $('.comparison-slide[data-section-index="' + i + '"] .slide-inner').html(comparison_slide_template(section_evaluated));
                }
            }
        }

        render_nav();
    }

    function render_nav() {
        var i = 0;
        var section;
        var nav_html = "";
        var current_slide = slides.find('.slick-current');
        var current_section_index = parseInt(current_slide.data('section-index'));

        applicable_sections_count = 0;

        for(section of sections) {
            if(! sections[0].complete || (! section.applicable_if || eval(section.applicable_if))) {
                nav_html += `<li data-slide-index="${i}" class="${section.complete ? 'complete' : 'not-complete'}">${section.label}</li>`;
            } else {
                nav_html += `<li data-slide-index="${i}" class="${section.complete ? 'complete' : 'not-complete'} not-applicable">${section.label}</li>`;
            }

            applicable_sections_count += 1;

            section.slide_index = i;

            if(section.comparable) {
                i += 2;
            } else {
                i += 1;
            }
        }

        nu_survey.find('.sections-nav ol').html(nav_html);
        nu_survey.find('.sections-nav li').off('click').on('click', function(e) {
            if($(this).hasClass('not-applicable')) {
                return;
            } else {
                $('.sections-nav').removeClass('open');

                var current_slide = slides.find('.slick-current');
                var current_slide_form = current_slide.find('form');
                var current_section_index = current_slide.data('section-index');

                if(current_section_index == 0) {
                    if(current_slide_form[0].reportValidity()) {
                        $.extend(responses, current_slide_form.serializeJSON());
                    } else {
                        return;
                    }
                }

                nu_survey.animate({ scrollTop: 0 }, 'fast');
                slides.slick('slickGoTo', $(this).data('slide-index'));
            }
        });

        if(current_section_index == 0) {
            nu_survey.find('.sections-overview').html(eval('`' + t2('Section 1 of ${applicable_sections_count} <i class="fa fa-caret-down"></i>') + '`'));
            actions.find('.begin').show();
            actions.find('.next').hide();
            actions.find('.compare').hide();
            actions.find('.print-results').hide();
            actions.find('.share-results').hide();
            actions.find('.start-over').hide();
        } else if(current_section_index + 1 >= sections.length) {
            nu_survey.find('.sections-overview').html(t2('Final Results <i class="fa fa-caret-down"></i>'));
            actions.find('.begin').hide();
            actions.find('.next').hide();
            actions.find('.compare').hide();
            actions.find('.print-results').show();
            actions.find('.share-results').show();
            actions.find('.start-over').show();
            record_finished();
        } else {
            nu_survey.find('.sections-overview').html(eval('`' + t2('Section ${current_section_index + 1} of ${applicable_sections_count} <i class="fa fa-caret-down"></i>') + '`'));

            if(current_slide.hasClass('comparison-slide')) {
                actions.find('.begin').hide();
                actions.find('.next').show();
                actions.find('.compare').hide();
                actions.find('.print-results').hide();
                actions.find('.share-results').hide();
                actions.find('.start-over').hide();
            } else {
                actions.find('.begin').hide();
                actions.find('.next').hide();
                actions.find('.compare').show();
                actions.find('.print-results').hide();
                actions.find('.share-results').hide();
                actions.find('.start-over').hide();
            }
        }
    }

    function render_final_results_slide() {
        var final_results_html = '', final_results_html_2 = '';

        if(!responses.mergeVars.masterZone) {
            return;
        }

        for(var k in sections) {
            if(sections[k].comparable) {
                var include_section = false;
                var section_evaluated = JSON.parse(JSON.stringify(sections[k]));

                if(section_evaluated['label_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['label'] = eval('`' + section_evaluated['label_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['label_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['label'] = eval('`' + section_evaluated['label_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['label']) {
                    section_evaluated['label'] = eval('`' + section_evaluated['label'] + '`');
                }

                if(section_evaluated['body_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['body'] = eval('`' + section_evaluated['body_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['body_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['body'] = eval('`' + section_evaluated['body_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['body']) {
                    section_evaluated['body'] = eval('`' + section_evaluated['body'] + '`');
                }

                for(var i in section_evaluated['blocks']) {
                    if(section_evaluated['blocks'][i]['type'] == 'question'
                    && responses[sections[k]['blocks'][i]['handle']] != undefined) {
                        if(section_evaluated['blocks'][i]['applicable_if'] == ''
                        || eval(section_evaluated['blocks'][i]['applicable_if']) == true) {
                            if(section_evaluated['blocks'][i]['label_' + responses.mergeVars.masterZone.contractName]) {
                                section_evaluated['blocks'][i]['label'] = eval('`' + section_evaluated['blocks'][i]['label_' + responses.mergeVars.masterZone.contractName] + '`');
                            } else if(section_evaluated['blocks'][i]['label_' + responses.mergeVars.masterZone.contractName]) {
                                section_evaluated['blocks'][i]['label'] = eval('`' + section_evaluated['blocks'][i]['label_' + responses.mergeVars.masterZone.contractName] + '`');
                            } else if(section_evaluated['blocks'][i]['label']) {
                                section_evaluated['blocks'][i]['label'] = eval('`' + section_evaluated['blocks'][i]['label'] + '`');
                            }
                            include_section = true;

                            if(sections[k]['blocks'][i]['flip_comparison_' + responses.mergeVars.masterZone.contractName] != undefined) {
                                var flip_comparison = sections[k]['blocks'][i]['flip_comparison_' + responses.mergeVars.masterZone.contractName];
                            } else {
                                var flip_comparison = sections[k]['blocks'][i]['flip_comparison'];
                            }

                            if(sections[k]['blocks'][i]['union_comparison_value_' + responses.mergeVars.masterZone.contractName]) {
                                var union_comparison_value = eval(sections[k]['blocks'][i]['union_comparison_value_' + responses.mergeVars.masterZone.contractName]);
                            } else {
                                var union_comparison_value = eval(sections[k]['blocks'][i]['union_comparison_value']);
                            }

                            var non_union_comparison_value = '';
                            if(sections[k]['blocks'][i]['field'] == 'radio') {
                                for(var m in sections[k]['blocks'][i]['choices']) {
                                    if(responses[sections[k]['blocks'][i]['handle']] == sections[k]['blocks'][i]['choices'][m]['value']) {
                                        non_union_comparison_value = sections[k]['blocks'][i]['choices'][m]['comparison_value'];
                                    }
                                }
                            } else {
                                non_union_comparison_value = eval(responses[sections[k]['blocks'][i]['handle']]);
                            }

                            if(sections[k]['blocks'][i]['field'] == 'boolean') {
                                section_evaluated['blocks'][i]['non_union_response'] = non_union_comparison_value ? t2('Yes') : t2('No');
                            } else if(sections[k]['blocks'][i]['field'] == 'radio') {
                                section_evaluated['blocks'][i]['non_union_response'] = responses[sections[k]['blocks'][i]['handle']];
                            } else if(sections[k]['blocks'][i]['field'] == 'money') {
                                section_evaluated['blocks'][i]['non_union_response'] = currency(non_union_comparison_value).format();
                            } else {
                                section_evaluated['blocks'][i]['non_union_response'] = non_union_comparison_value;
                            }

                            if(section_evaluated['blocks'][i].evaluable) {
                                if((flip_comparison ? (union_comparison_value < non_union_comparison_value) : (union_comparison_value > non_union_comparison_value))) {
                                    scores[sections[k]['blocks'][i]['handle']] = {union: 1, non_union: 0};
                                    section_evaluated['blocks'][i]['union_won'] = 1;
                                    section_evaluated['blocks'][i]['non_union_won'] = 0;
                                } else if((flip_comparison ? (union_comparison_value > non_union_comparison_value) : (union_comparison_value < non_union_comparison_value))) {
                                    scores[sections[k]['blocks'][i]['handle']] = {union: 0, non_union: 1};
                                    section_evaluated['blocks'][i]['union_won'] = 0;
                                    section_evaluated['blocks'][i]['non_union_won'] = 1;
                                } else {
                                    scores[sections[k]['blocks'][i]['handle']] = {union: 1, non_union: 1};
                                    section_evaluated['blocks'][i]['union_won'] = 1;
                                    section_evaluated['blocks'][i]['non_union_won'] = 1;
                                }
                            }
                        } else {
                            scores[sections[k]['blocks'][i]['handle']] = {union: 0, non_union: 0};
                            section_evaluated['blocks'][i]['union_won'] = 0;
                            section_evaluated['blocks'][i]['non_union_won'] = 0;
                        }
                    }

                    if(section_evaluated['blocks'][i]['union_response_' + responses.mergeVars.masterZone.contractName + '_' + responses[section_evaluated['blocks'][i].handle]]) {
                        section_evaluated['blocks'][i]['union_response'] = eval('`' + sections[k]['blocks'][i]['union_response_' + responses.mergeVars.masterZone.contractName + '_' + responses[section_evaluated['blocks'][i].handle]] + '`');
                    } else if(section_evaluated['blocks'][i]['union_response_' + responses.mergeVars.masterZone.contractName]) {
                        section_evaluated['blocks'][i]['union_response'] = eval('`' + sections[k]['blocks'][i]['union_response_' + responses.mergeVars.masterZone.contractName] + '`');
                    } else if(section_evaluated['blocks'][i]['union_response']) {
                        section_evaluated['blocks'][i]['union_response'] = eval('`' + sections[k]['blocks'][i]['union_response'] + '`');
                    }
                }

                if(include_section) {
                    final_results_html += comparison_slide_template(section_evaluated);
                }
            }
        }

        final_results_html_2 = '<div class="winner">';
        if(union_score > non_union_score) {
            final_results_html_2 += '<h1>' + t2('Union workers have better conditions in your area.') + '</h1>';
        } else {
            final_results_html_2 += '<h1>' + t2('It seems like your working conditions are good, but without a union contract, this could change at any time.') + '</h1>';
        }

        final_results_html_2 += '<p>' + eval('`' + t2('<a target="_blank" href="/non-union-workers?s_fe_key2=${fe_key || s_fe_key}#contact-us--it-s-confidential">Click here</a> if you are interested in learning more about how to organize your workplace and win improvements to your wages, benefits, and working conditions!') + '`') + '</p></div>';


        $('#final-results .slide-inner').html(final_results_html_2 + final_results_html);
    }

    function render_results_slides() {
        var current_slide = slides.find('.slick-current');
        var current_slide_form = current_slide.find('form');
        var current_section_index = parseInt(current_slide.data('section-index'));

        $.extend(responses, current_slide_form.serializeJSON());

        if(!responses.mergeVars.masterZone) {
            return;
        }

        if(sections[current_section_index].comparable) {
            var section_evaluated = JSON.parse(JSON.stringify(sections[current_section_index]));

            if(section_evaluated['label_' + responses.mergeVars.masterZone.contractName]) {
                section_evaluated['label'] = eval('`' + section_evaluated['label_' + responses.mergeVars.masterZone.contractName] + '`');
            } else if(section_evaluated['label_' + responses.mergeVars.masterZone.contractName]) {
                section_evaluated['label'] = eval('`' + section_evaluated['label_' + responses.mergeVars.masterZone.contractName] + '`');
            } else if(section_evaluated['label']) {
                section_evaluated['label'] = eval('`' + section_evaluated['label'] + '`');
            }

            if(section_evaluated['body_' + responses.mergeVars.masterZone.contractName]) {
                section_evaluated['body'] = eval('`' + section_evaluated['body_' + responses.mergeVars.masterZone.contractName] + '`');
            } else if(section_evaluated['body_' + responses.mergeVars.masterZone.contractName]) {
                section_evaluated['body'] = eval('`' + section_evaluated['body_' + responses.mergeVars.masterZone.contractName] + '`');
            } else if(section_evaluated['body']) {
                section_evaluated['body'] = eval('`' + section_evaluated['body'] + '`');
            }

            for(var i in section_evaluated['blocks']) {
                if(section_evaluated['blocks'][i]['type'] == 'question'
                && (section_evaluated['blocks'][i]['applicable_if'] == '' || eval(section_evaluated['blocks'][i]['applicable_if']) == true)
                && responses[section_evaluated['blocks'][i]['handle']] != undefined) {
                    if(section_evaluated['blocks'][i]['label_' + responses.mergeVars.masterZone.contractName]) {
                        section_evaluated['blocks'][i]['label'] = eval('`' + section_evaluated['blocks'][i]['label_' + responses.mergeVars.masterZone.contractName] + '`');
                    } else if(section_evaluated['blocks'][i]['label_' + responses.mergeVars.masterZone.contractName]) {
                        section_evaluated['blocks'][i]['label'] = eval('`' + section_evaluated['blocks'][i]['label_' + responses.mergeVars.masterZone.contractName] + '`');
                    } else if(section_evaluated['blocks'][i]['label']) {
                        section_evaluated['blocks'][i]['label'] = eval('`' + section_evaluated['blocks'][i]['label'] + '`');
                    }

                    if(section_evaluated['blocks'][i]['flip_comparison_' + responses.mergeVars.masterZone.contractName] != undefined) {
                        var flip_comparison = section_evaluated['blocks'][i]['flip_comparison_' + responses.mergeVars.masterZone.contractName];
                    } else {
                        var flip_comparison = section_evaluated['blocks'][i]['flip_comparison'];
                    }

                    var union_comparison_value = eval(section_evaluated['blocks'][i]['union_comparison_value']);

                    if(section_evaluated['blocks'][i]['union_comparison_value_' + responses.mergeVars.masterZone.contractName]) {
                        var union_comparison_value = eval(section_evaluated['blocks'][i]['union_comparison_value_' + responses.mergeVars.masterZone.contractName]);
                    } else {
                        var union_comparison_value = eval(section_evaluated['blocks'][i]['union_comparison_value']);
                    }

                    var non_union_comparison_value = '';
                    if(section_evaluated['blocks'][i]['field'] == 'radio') {
                        for(var m in section_evaluated['blocks'][i]['choices']) {
                            if(responses[section_evaluated['blocks'][i]['handle']] == section_evaluated['blocks'][i]['choices'][m]['value']) {
                                non_union_comparison_value = section_evaluated['blocks'][i]['choices'][m]['comparison_value'];
                            }
                        }
                    } else {
                        non_union_comparison_value = eval(responses[section_evaluated['blocks'][i]['handle']]);
                    }

                    if(section_evaluated['blocks'][i]['field'] == 'boolean') {
                        section_evaluated['blocks'][i]['non_union_response'] = non_union_comparison_value ? t2('Yes') : t2('No');
                    } else if(section_evaluated['blocks'][i]['field'] == 'radio') {
                        section_evaluated['blocks'][i]['non_union_response'] = responses[section_evaluated['blocks'][i]['handle']];
                    } else if(section_evaluated['blocks'][i]['field'] == 'money') {
                        section_evaluated['blocks'][i]['non_union_response'] = currency(non_union_comparison_value).format();
                    } else {
                        section_evaluated['blocks'][i]['non_union_response'] = non_union_comparison_value;
                    }

                    if(section_evaluated['blocks'][i].evaluable) {
                        if((flip_comparison ? (union_comparison_value < non_union_comparison_value) : (union_comparison_value > non_union_comparison_value))) {
                            scores[section_evaluated['blocks'][i]['handle']] = {union: 1, non_union: 0};
                            section_evaluated['blocks'][i]['union_won'] = 1;
                            section_evaluated['blocks'][i]['non_union_won'] = 0;
                        } else if((flip_comparison ? (union_comparison_value > non_union_comparison_value) : (union_comparison_value < non_union_comparison_value))) {
                            scores[section_evaluated['blocks'][i]['handle']] = {union: 0, non_union: 1};
                            section_evaluated['blocks'][i]['union_won'] = 0;
                            section_evaluated['blocks'][i]['non_union_won'] = 1;
                        } else {
                            scores[section_evaluated['blocks'][i]['handle']] = {union: 1, non_union: 1};
                            section_evaluated['blocks'][i]['union_won'] = 1;
                            section_evaluated['blocks'][i]['non_union_won'] = 1;
                        }
                    }
                }

                if(section_evaluated['blocks'][i]['union_response_' + responses.mergeVars.masterZone.contractName + '_' + responses[section_evaluated['blocks'][i].handle]]) {
                    section_evaluated['blocks'][i]['union_response'] = eval('`' + section_evaluated['blocks'][i]['union_response_' + responses.mergeVars.masterZone.contractName + '_' + responses[section_evaluated['blocks'][i].handle]] + '`');
                } else if(section_evaluated['blocks'][i]['union_response_' + responses.mergeVars.masterZone.contractName]) {
                    section_evaluated['blocks'][i]['union_response'] = eval('`' + sections[current_section_index]['blocks'][i]['union_response_' + responses.mergeVars.masterZone.contractName] + '`');
                } else if(section_evaluated['blocks'][i]['union_response']) {
                    section_evaluated['blocks'][i]['union_response'] = eval('`' + sections[current_section_index]['blocks'][i]['union_response'] + '`');
                }
            }

            nu_survey.find('.comparison-slide[data-section-index="' + current_section_index + '"] .slide-inner').html(comparison_slide_template(section_evaluated));
        }

        render_final_results_slide();
        render_nav();
        toggle_applicable_questions();
    }

    var toggle_applicable_questions = debounce(function(e) {
        if(!responses.mergeVars.masterZone) {
            return;
        }

        var current_slide = slides.find('.slick-current');
        var current_section_index = parseInt(current_slide.data('section-index'));
        var section = sections[current_section_index];

        for(var section of sections) {
            if(section.blocks) {
                for(var block of section['blocks']) {
                    if(block.type == 'question') {
                        if(block.applicable_if == '') {
                            $('.question_' + block.handle).find('[data-required="required"]').attr('required', 'required');
                        } else if(eval(block.applicable_if) == true) {
                            if($('.question_' + block.handle).css('display') == 'none') {
                                $('.question_' + block.handle).slideDown();
                            }
                            $('.question_' + block.handle).find('[data-required="required"]').attr('required', 'required');
                            $('.question_response_' + block.handle).slideDown();
                        } else {
                            if($('.question_' + block.handle).css('display') != 'none') {
                                $('.question_' + block.handle).slideUp();
                            }
                            $('.question_' + block.handle).find('[required]').removeAttr('required');
                            $('.question_response_' + block.handle).hide();
                        }
                    } else if(block.type == 'sectionheader') {
                        if(block.applicable_if != undefined) {
                            if((block.applicable_if != '' && eval(block.applicable_if) == true)) {
                                if($('#sectionheader-' + block.handle).css('display') == 'none') {
                                    $('#sectionheader-' + block.handle).slideDown();
                                }
                            } else {
                                if($('#sectionheader-' + block.handle).css('display') != 'none') {
                                    $('#sectionheader-' + block.handle).slideUp();
                                }
                            }
                        }
                    } else if(block.type == 'subsectionheader') {
                        if(block.applicable_if != undefined) {
                            if((block.applicable_if != '' && eval(block.applicable_if) == true)) {
                                if($('#subsection-' + block.handle).css('display') == 'none') {
                                    $('#subsection-' + block.handle).slideDown();
                                }

                                $('.question_response_' + block.handle).slideDown();
                            } else {
                                if($('#subsection-' + block.handle).css('display') != 'none') {
                                    $('#subsection-' + block.handle).slideUp();
                                }

                                $('.question_response_' + block.handle).hide();
                            }
                        }
                    }
                }
            }
        }

        slides.slick('reinit');
    }, 250);

    function record_finished() {
        if(fe_key) {
            $.ajax({
                url:'/form-entries/store',
                type: 'post',
                data: $.extend(responses, {
                    'fe_state': 'complete',
                    'language': localeName,
                    'fid': fid,
                    'fe_key': fe_key,
                    'h_fe_key': h_fe_key,
                    's_fe_key': s_fe_key
                }),
                dataType: 'json'
            }).done(function(response) {
                fe_key = response.fe_key;
                h_fe_key = response.h_fe_key;
            });
        }
    }

    function store_responses() {
        $.ajax({
            url:'/form-entries/store',
            type: 'post',
            data: $.extend(responses, {
                'fe_state': 'pending',
                'language': localeName,
                'fid': fid,
                'fe_key': fe_key,
                'h_fe_key': h_fe_key,
                's_fe_key': s_fe_key
            }),
            dataType: 'json'
        }).done(function(response) {
            fe_key = response.fe_key;
            h_fe_key = response.h_fe_key;
        });
    }

    function calc_scores() {
        union_score = 0;
        non_union_score = 0;
        for(var i in scores) {
            union_score += scores[i]['union'];
            non_union_score += scores[i]['non_union'];
        }
        $('.union-score').html(union_score);
        $('.non-union-score').html(non_union_score);
    }

    function next_slide(e) {
        if(e) {
            e.preventDefault();
        }

        var current_slide = slides.find('.slick-current');
        var current_slide_form = current_slide.find('form');
        var current_section_index = parseInt(current_slide.data('section-index'));

        if(current_slide_form.length > 0) {
            if(current_slide_form[0].reportValidity()) {
                if(current_section_index == 0) {
                    if(responses.classification != previous_classification || responses.zip != previous_zip) {
                        previous_classification = '';
                        previous_zip = '';
                        scores = {};
                        responses = {
                            mergeVars: {
                                masterZone: '',
                                wageRate: '',
                                wageItemRates: '',
                                classification: ''
                            }
                        };
                        for(var i=1; i < sections.length; i++) {
                            sections[i].complete = false;
                        }
                        $.extend(responses, current_slide_form.serializeJSON());
                    }

                    calc_merge_vars();
                    apply_merge_vars();
                }

                if(!responses.mergeVars.masterZone) {
                    Swal.fire({
                        title: t2('You have entered a zip code outside of our jurisdiction in New York and New Jersey. Please enter a zip code within our jurisdiction to continue.'),
                        icon: 'warning'
                    });
                } else {
                    calc_scores();
                    store_responses();
                    sections[current_section_index].complete = true;
                    slides.slick('slickNext');
                }
            }
        } else if(current_section_index + 2 >= sections.length) {
            var incomplete_sections = '';

            for(var i = 1; i < sections.length - 1; i++) {
                if(!sections[i].complete && (! sections[i].applicable_if || eval(sections[i].applicable_if))) {
                    incomplete_sections += '<li>' + sections[i].label + '</li>';
                }
            }

            if(incomplete_sections == '') {
                slides.slick('slickNext');
            } else {
                Swal.fire({
                    title: t2('You haven\'t responded to the following sections:'),
                    html: '<ul style="text-align:left">' + incomplete_sections + '</ul>',
                    icon: 'question',
                    confirmButtonText: t2('Complete Survey'),
                    cancelButtonText: t2('View Partial Results'),
                    showCancelButton: true,
                    cancelButtonColor: '#3085d6',
                }).then(function(response) {
                    if(response.isConfirmed ) {
                        for(var i = 1; i < sections.length; i++) {
                            if(!sections[i].complete && (! sections[i].applicable_if || eval(sections[i].applicable_if))) {
                                slides.slick('slickGoTo', $('[data-section-index="' + i + '"]').attr('data-slick-index'));
                                break;
                            }
                        }
                    } else {
                        slides.slick('slickNext');
                    }
                });
            }
        } else {
            for(var i = 1; i < sections.length; i++) {
                if(!sections[i].complete && (! sections[i].applicable_if || eval(sections[i].applicable_if))) {
                    slides.slick('slickGoTo', $('[data-section-index="' + i + '"]').attr('data-slick-index'));
                    break;
                }
            }
        }

        return false;
    }

    function print_results() {
        $('body').removeClass('no-scroll');
        $('body').addClass('print-nu-survey');
        print();
        $('body').removeClass('print-nu-survey');
        $('body').addClass('no-scroll');
    }

    function share_results() {
        var share_btns_template = Handlebars.compile(document.getElementById("share-btns-template").innerHTML);
        var safari_desktop = /Safari/i.test(navigator.userAgent) &&
            /Apple Computer/.test(navigator.vendor) &&
            !/Mobi|Android/i.test(navigator.userAgent);
        var url = 'https://' + window.location.hostname + window.location.pathname + '?s_fe_key=' + (fe_key || s_fe_key);

        if (!safari_desktop && navigator.share) {
            navigator.share({
                title: t2('Union vs Non-union Comparison Survey'),
                url: url
            });
        } else {
            Swal.fire({
                title: 'Share',
                showCloseButton: true,
                showCancelButton: false,
                showConfirmButton: false,
                width: '400px',
                html:
                    share_btns_template({
                        title: t2('Union vs Non-union Comparison Survey'),
                        url: url
                    })
            });

            window.Sharer.init();
        }
    }

    function start_over() {
        previous_classification = '';
        previous_zip = '';
        scores = {};
        responses = {
            mergeVars: {
                masterZone: '',
                wageRate: '',
                wageItemRates: '',
                classification: ''
            }
        };
        for(var section of sections) {
            section.complete = false;
        }
        nu_survey.find('.question input[type="text"], .question input[type="money"], .question input[type="number"], .question select').val('');
        nu_survey.find('.question input:checked').prop('checked', false);
        slides.slick('slickGoTo', 0);
        calc_scores();
        render_nav();
    }

    $('body').on('keyup change click', '#union-comparison-survey form input, #union-comparison-survey form select, #union-comparison-survey form textarea', function() {
        var form = $(this).closest('form');
        setTimeout(function() {
            if(previous_classification == '' && previous_zip == '') {
                previous_classification = responses.classification;
                previous_zip = responses.zip;
            }

            render_results_slides();
        }, 10);
    });

    $.getJSON(localeUrlPath + '/nu-survey-questions.json', function(sections2) {
        sections = sections2;
        sections.push({label: t2('Final results'), comparable: false});

        var params = new URLSearchParams(window.location.search);

        if(params.get('s_fe_key')) {
            s_fe_key = params.get('s_fe_key');

            $.ajax({
                url:'/form-entries/get',
                type: 'get',
                data: {
                    'fid': fid,
                    'fe_key': params.get('s_fe_key')
                },
                dataType: 'json'
            }).done(function(response) {

                responses = response;

                for(var section of sections) {
                    if(section.blocks) {
                        for(var block of section['blocks']) {
                            if(block.type == 'question' && block.handle in responses) {
                                if(block.field == 'boolean') {
                                    if(responses[block.handle] == true) {
                                        nu_survey.find('#' + block.handle + '_yes').prop('checked', true);
                                    } else {
                                        nu_survey.find('#' + block.handle + '_no').prop('checked', true);
                                    }
                                } else if(block.field == 'radio') {
                                } else if(block.field == 'select' || block.field == 'money' ||
                                block.field == 'number' || block.field == 'input' || block.field == 'zip') {
                                    nu_survey.find('#' + block.handle).val(responses[block.handle]);
                                }
                            }
                        }
                    }
                }

                render_final_results_slide();
                calc_scores();
                $('.union-comparison-survey').click();
                slides.slick('slickGoTo', slides.find('.slide').length - 1);
            });
        }

        applicable_sections_count = sections.length;

        for(var i=1; i < sections.length; i++) {
            for(var k in sections[i].blocks) {
                /* Allow translations to be accessible in the templates */
                sections[i].yes = t2('Yes');
                sections[i].no = t2('No');
                sections[i].union = t2('Union');
                sections[i].nonUnion = t2('Non-Union');

                if(sections[i]['blocks'][k]['type'] == 'sectionheader' || sections[i]['blocks'][k]['type'] == 'subsectionheader') {
                    sections[i]['blocks'][k]['handle'] = slugify(sections[i]['blocks'][k]['body']);
                }
            }
        }

        nu_survey.find('.close').off('click').on('click', function(e) {
            $('body').removeClass('no-scroll').removeClass('nu-survey-active');
            nu_survey.hide();
            e.preventDefault();
            return false;
        });

        /* close if the escape key is pressed */
        $(document).off('keyup').on('keyup', function(e) {
            if (e.keyCode === 27) {
                $('body').removeClass('no-scroll nu-survey-active');
                nu_survey.hide();
            }
        });

        render_nav();

        nu_survey.find('.sections-overview').off('click').on('click', function() {
            $('.sections-nav').toggleClass('open');
        });

        slides.html(nu_survey_template({sections: sections}));

        if(!sections[0].complete) {
            $('.question-slide[data-section-index="' + 0 + '"] .slide-inner').html(question_slide_template(sections[0]));
        }

        nu_survey.find('form').off('submit').on('submit', next_slide);
        actions.find('.begin').off('click').on('click', next_slide);
        actions.find('.next').off('click').on('click', next_slide);
        actions.find('.compare').off('click').on('click', next_slide);
        actions.find('.print-results').off('click').on('click', print_results);
        actions.find('.share-results').off('click').on('click', share_results);
        actions.find('.start-over').off('click').on('click', start_over);


        $('.union-comparison-survey').off('click').on('click', function() {
            $('body').trigger('union-comparison-survey');
        });

        $('body').off('union-comparison-survey').on('union-comparison-survey', function(e) {
            $('body').addClass('no-scroll').addClass('nu-survey-active');
            nu_survey.show();

            if(! nu_survey.hasClass('initialized')) {
                slides.slick({
                    infinite: false,
                    slidesToShow: 1,
                    arrows: false,
                    dots: false,
                    draggable: false,
                    autoplay: false,
                    adaptiveHeight: true,
                    focusOnSelect: false,
                    fade: true,
                    accessibility: false
                }).on('afterChange', function(e) {
                    var current_slide = slides.find('.slick-current');
                    var current_section_index = parseInt(current_slide.data('section-index'));

                    render_nav();
                    toggle_applicable_questions();

                    nu_survey.animate({ scrollTop: 0 }, 'fast');
                });
            }

            toggle_applicable_questions();
            render_nav();

            nu_survey.addClass('initialized');

            e.preventDefault();
            return false;
        });
    });
}
