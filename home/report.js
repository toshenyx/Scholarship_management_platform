document.addEventListener(
    'DOMContentLoaded',
    () => {

        initScholarshipReport();

    }
);


//INITIALIZE REPORT

function initScholarshipReport() {

    const report =
        document.getElementById(
            'scholarship-report'
        );

    if (!report) {
        return;
    }


    const printButton =
        document.getElementById(
            'print-report-btn'
        );

    const refreshButton =
        document.getElementById(
            'refresh-report-btn'
        );


    if (printButton) {

        printButton.addEventListener(
            'click',
            () => {

                window.print();

            }
        );
    }


    if (refreshButton) {

        refreshButton.addEventListener(
            'click',
            () => {

                loadScholarshipReport();

            }
        );
    }


    loadScholarshipReport();
}


//LOAD REPORT

async function loadScholarshipReport() {

    const report =
        document.getElementById(
            'scholarship-report'
        );

    const status =
        document.getElementById(
            'report-status'
        );


    if (!report) {
        return;
    }


    report.hidden = true;


    if (status) {

        status.style.display =
            'block';

        status.className =
            'report-status no-print';

        status.textContent =
            'Building your scholarship report...';
    }


    try {

        const response =
            await fetch(
                '../backend/get_report_data.php',
                {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        const data =
            await response.json();


        console.log(
            'SCHOLARSHIP REPORT:',
            data
        );


        //LOGIN

        if (
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }


        // PROFILE INCOMPLETE

        if (
            data.profile_complete === false
        ) {

            displayReportAccessError(
                data.message ||
                'Complete your profile before generating a report.',
                data.missing_fields || []
            );

            return;
        }


        // ERROR

        if (
            !response.ok ||
            !data.success
        ) {

            throw new Error(
                data.message ||
                'Unable to generate scholarship report.'
            );
        }


        //RENDER

        renderScholarshipReport(
            data.report
        );


        if (status) {

            status.style.display =
                'none';
        }


        report.hidden =
            false;


    } catch (error) {

        console.error(
            'Scholarship report error:',
            error
        );


        if (status) {

            status.style.display =
                'block';

            status.className =
                'report-status report-status--error no-print';

            status.innerHTML = `
                <strong>
                    Unable to generate report.
                </strong>

                <span>
                    ${escapeReportHtml(
                error.message ||
                'Please try again.'
            )}
                </span>
            `;
        }
    }
}


// RENDER COMPLETE REPORT

function renderScholarshipReport(
    reportData
) {

    if (!reportData) {
        return;
    }


    renderReportDate(
        reportData.generated_at
    );


    renderStudentProfile(
        reportData.student || {}
    );


    renderReportStatistics(
        reportData.statistics || {}
    );


    renderReportRecommendations(
        Array.isArray(
            reportData.recommendations
        )
            ? reportData.recommendations
            : []
    );


    renderSavedScholarships(
        Array.isArray(
            reportData.saved_scholarships
        )
            ? reportData.saved_scholarships
            : []
    );
}


// REPORT DATE

function renderReportDate(
    dateValue
) {

    const element =
        document.getElementById(
            'report-generated-date'
        );


    if (!element) {
        return;
    }


    if (!dateValue) {

        element.textContent =
            new Date()
                .toLocaleString();

        return;
    }


    const parsedDate =
        new Date(
            dateValue.replace(
                ' ',
                'T'
            )
        );


    if (
        Number.isNaN(
            parsedDate.getTime()
        )
    ) {

        element.textContent =
            dateValue;

        return;
    }


    element.textContent =
        parsedDate.toLocaleString(
            undefined,
            {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }
        );
}


// STUDENT PROFILE

function renderStudentProfile(
    student
) {

    const container =
        document.getElementById(
            'report-profile-grid'
        );


    if (!container) {
        return;
    }


    const fullName =
        student.full_name ||
        student.username ||
        'Not specified';


    const preferredCountry =
        student.preferred_country ||
        'Any Country';


    const profileItems = [

        [
            'Student',
            fullName
        ],

        [
            'Email',
            student.email ||
            'Not specified'
        ],

        [
            'Nationality',
            student.nationality ||
            'Not specified'
        ],

        [
            'Country of Residence',
            student.country_of_residence ||
            'Not specified'
        ],

        [
            'Education Level',
            student.education_level ||
            'Not specified'
        ],

        [
            'Institution',
            student.institution ||
            'Not specified'
        ],

        [
            'Current Course',
            student.course ||
            'Not specified'
        ],

        [
            'GPA',
            student.gpa ||
            'Not specified'
        ],

        [
            'Graduation Year',
            student.graduation_year ||
            'Not specified'
        ],

        [
            'Preferred Field',
            student.preferred_field ||
            'Not specified'
        ],

        [
            'Preferred Country',
            preferredCountry
        ],

        [
            'Funding Preference',
            formatReportFunding(
                student.funding_preference
            )
        ]

    ];


    container.innerHTML =
        profileItems
            .map(
                ([label, value]) => `

                    <div class="report-profile-item">

                        <span>
                            ${escapeReportHtml(
                    label
                )}
                        </span>

                        <strong>
                            ${escapeReportHtml(
                    value
                )}
                        </strong>

                    </div>

                `
            )
            .join('');
}


// STATISTICS

function renderReportStatistics(
    statistics
) {

    const matchCount =
        document.getElementById(
            'report-match-count'
        );

    const savedCount =
        document.getElementById(
            'report-saved-count'
        );


    if (matchCount) {

        matchCount.textContent =
            Number(
                statistics.recommendation_count
                ?? 0
            );
    }


    if (savedCount) {

        savedCount.textContent =
            Number(
                statistics.saved_count
                ?? 0
            );
    }
}


// RECOMMENDATIONS

function renderReportRecommendations(
    scholarships
) {

    const container =
        document.getElementById(
            'report-recommendations'
        );


    if (!container) {
        return;
    }


    container.innerHTML = '';


    if (
        scholarships.length === 0
    ) {

        container.innerHTML = `
            <div class="report-empty">

                No personalized scholarship
                matches were available when
                this report was generated.

            </div>
        `;

        return;
    }


    scholarships.forEach(
        (
            scholarship,
            index
        ) => {

            const card =
                document.createElement(
                    'article'
                );


            card.className =
                'report-scholarship-card';


            const reasons =
                Array.isArray(
                    scholarship.match_reasons
                )
                    ? scholarship.match_reasons
                    : [];


            const warnings =
                Array.isArray(
                    scholarship.warnings
                )
                    ? scholarship.warnings
                    : [];


            const reasonsHtml =
                reasons.length > 0
                    ? `
                        <div class="report-match-block">

                            <strong>
                                Why this scholarship matches
                            </strong>

                            <ul>

                                ${reasons
                        .map(
                            reason => `
                                                <li>
                                                    ✓
                                                    ${escapeReportHtml(
                                reason
                            )}
                                                </li>
                                            `
                        )
                        .join('')
                    }

                            </ul>

                        </div>
                    `
                    : '';


            const warningsHtml =
                warnings.length > 0
                    ? `
                        <div class="report-warning-block">

                            <strong>
                                Check before applying
                            </strong>

                            <ul>

                                ${warnings
                        .map(
                            warning => `
                                                <li>
                                                    ${escapeReportHtml(
                                warning
                            )}
                                                </li>
                                            `
                        )
                        .join('')
                    }

                            </ul>

                        </div>
                    `
                    : '';


            card.innerHTML = `

                <div class="report-scholarship-header">

                    <div>

                        <span class="report-scholarship-number">
                            Match ${index + 1}
                        </span>

                        <h3>
                            ${escapeReportHtml(
                scholarship.title ||
                'Untitled Scholarship'
            )}
                        </h3>

                        <p>
                            ${escapeReportHtml(
                scholarship.provider ||
                'Provider not specified'
            )}
                        </p>

                    </div>


                    <div class="report-match-score">

                        <strong>
                            ${Number(
                scholarship.match_score
                ?? 0
            )}%
                        </strong>

                        <span>
                            ${escapeReportHtml(
                scholarship.match_label ||
                'Match'
            )}
                        </span>

                    </div>

                </div>


                <p class="report-scholarship-description">

                    ${escapeReportHtml(
                scholarship.description ||
                'No description provided.'
            )}

                </p>


                <div class="report-scholarship-meta">

                    ${reportMetaItem(
                'Country',
                scholarship.country
            )}

                    ${reportMetaItem(
                'University',
                scholarship.university
            )}

                    ${reportMetaItem(
                'Education Level',
                scholarship.education_level
            )}

                    ${reportMetaItem(
                'Eligible Courses',
                scholarship.eligible_courses
            )}

                    ${reportMetaItem(
                'Minimum GPA',
                scholarship.minimum_gpa
            )}

                    ${reportMetaItem(
                'Eligible Nationalities',
                scholarship.eligible_nationalities
            )}

                    ${reportMetaItem(
                'Funding',
                formatReportFunding(
                    scholarship.funding_type
                )
            )}

                    ${reportMetaItem(
                'Duration',
                scholarship.duration
            )}

                    ${reportMetaItem(
                'Deadline',
                formatReportDate(
                    scholarship.deadline
                )
            )}

                </div>


                ${reasonsHtml}

                ${warningsHtml}


                ${safeReportUrl(
                scholarship.application_link
            )
                    ? `
                            <div class="report-application-link">

                                <strong>
                                    Application:
                                </strong>

                                <a
                                    href="${escapeReportHtml(
                        safeReportUrl(
                            scholarship.application_link
                        )
                    )}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    ${escapeReportHtml(
                        scholarship.application_link
                    )}
                                </a>

                            </div>
                        `
                    : ''
                }

            `;


            container.appendChild(
                card
            );

        }
    );
}


// SAVED SCHOLARSHIPS

function renderSavedScholarships(
    scholarships
) {

    const container =
        document.getElementById(
            'report-saved-scholarships'
        );


    if (!container) {
        return;
    }


    container.innerHTML = '';


    if (
        scholarships.length === 0
    ) {

        container.innerHTML = `
            <div class="report-empty">

                You have not saved any
                scholarships yet.

            </div>
        `;

        return;
    }


    scholarships.forEach(
        (
            scholarship,
            index
        ) => {

            const card =
                document.createElement(
                    'article'
                );


            card.className =
                'report-scholarship-card report-saved-card';


            card.innerHTML = `

                <div class="report-scholarship-header">

                    <div>

                        <span class="report-scholarship-number">
                            Saved ${index + 1}
                        </span>

                        <h3>
                            ${escapeReportHtml(
                scholarship.title ||
                'Untitled Scholarship'
            )}
                        </h3>

                        <p>
                            ${escapeReportHtml(
                scholarship.provider ||
                'Provider not specified'
            )}
                        </p>

                    </div>

                </div>


                <p class="report-scholarship-description">

                    ${escapeReportHtml(
                scholarship.description ||
                'No description provided.'
            )}

                </p>


                <div class="report-scholarship-meta">

                    ${reportMetaItem(
                'Country',
                scholarship.country
            )}

                    ${reportMetaItem(
                'University',
                scholarship.university
            )}

                    ${reportMetaItem(
                'Education Level',
                scholarship.education_level
            )}

                    ${reportMetaItem(
                'Funding',
                formatReportFunding(
                    scholarship.funding_type
                )
            )}

                    ${reportMetaItem(
                'Deadline',
                formatReportDate(
                    scholarship.deadline
                )
            )}

                    ${reportMetaItem(
                'Saved',
                formatReportDateTime(
                    scholarship.saved_at
                )
            )}

                </div>


                ${safeReportUrl(
                scholarship.application_link
            )
                    ? `
                            <div class="report-application-link">

                                <strong>
                                    Application:
                                </strong>

                                <a
                                    href="${escapeReportHtml(
                        safeReportUrl(
                            scholarship.application_link
                        )
                    )}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    ${escapeReportHtml(
                        scholarship.application_link
                    )}
                                </a>

                            </div>
                        `
                    : ''
                }

            `;


            container.appendChild(
                card
            );

        }
    );
}


// PROFILE INCOMPLETE

function displayReportAccessError(
    message,
    missingFields
) {

    const report =
        document.getElementById(
            'scholarship-report'
        );

    const status =
        document.getElementById(
            'report-status'
        );


    if (report) {

        report.hidden =
            true;
    }


    if (!status) {
        return;
    }


    const fields =
        Array.isArray(
            missingFields
        )
            ? missingFields
            : [];


    status.style.display =
        'block';


    status.className =
        'report-status report-status--error no-print';


    status.innerHTML = `

        <strong>
            Scholarship report locked
        </strong>

        <span>
            ${escapeReportHtml(
        message
    )}
        </span>

        ${fields.length > 0
            ? `
                    <span>
                        Missing:
                        ${escapeReportHtml(
                fields.join(', ')
            )}
                    </span>
                `
            : ''
        }

        <a
            href="../profile/profile1.html"
            class="report-primary-btn"
        >
            Complete Profile
        </a>

    `;
}


// META ITEM

function reportMetaItem(
    label,
    value
) {

    let displayValue =
        value;


    if (
        displayValue === null ||
        displayValue === undefined ||
        displayValue === ''
    ) {

        displayValue =
            'Not specified';
    }


    return `

        <div class="report-meta-item">

            <span>
                ${escapeReportHtml(
        label
    )}
            </span>

            <strong>
                ${escapeReportHtml(
        displayValue
    )}
            </strong>

        </div>

    `;
}


// FUNDING FORMAT

function formatReportFunding(
    funding
) {

    if (!funding) {
        return 'Not specified';
    }


    switch (funding) {

        case 'fully_funded':
            return 'Fully Funded';

        case 'partially_funded':
            return 'Partially Funded';

        case 'tuition_only':
            return 'Tuition Only';

        default:

            return String(
                funding
            )
                .replaceAll(
                    '_',
                    ' '
                )
                .replace(
                    /\b\w/g,
                    character =>
                        character.toUpperCase()
                );
    }
}


// DATE

function formatReportDate(
    dateValue
) {

    if (!dateValue) {
        return 'Not specified';
    }


    const date =
        new Date(
            `${dateValue}T00:00:00`
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return dateValue;
    }


    return date.toLocaleDateString(
        undefined,
        {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }
    );
}


// DATE + TIME

function formatReportDateTime(
    value
) {

    if (!value) {
        return 'Not specified';
    }


    const date =
        new Date(
            value.replace(
                ' ',
                'T'
            )
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return value;
    }


    return date.toLocaleString(
        undefined,
        {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }
    );
}


// SAFE APPLICATION URL

function safeReportUrl(
    value
) {

    if (!value) {
        return '';
    }


    try {

        const url =
            new URL(
                value,
                window.location.origin
            );


        if (
            url.protocol !== 'http:' &&
            url.protocol !== 'https:'
        ) {

            return '';
        }


        return url.href;

    } catch (error) {

        return '';
    }
}


// ESCAPE HTML

function escapeReportHtml(
    value
) {

    if (
        value === null ||
        value === undefined
    ) {

        return '';
    }


    return String(
        value
    )

        .replace(
            /&/g,
            '&amp;'
        )

        .replace(
            /</g,
            '&lt;'
        )

        .replace(
            />/g,
            '&gt;'
        )

        .replace(
            /"/g,
            '&quot;'
        )

        .replace(
            /'/g,
            '&#039;'
        );
}