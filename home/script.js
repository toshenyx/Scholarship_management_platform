document.addEventListener('DOMContentLoaded', () => {

    initMultiselects();
    initFileUploads();
    initFilterPills();
    initShortlistHearts();
    initAuthForms();
    initPasswordToggles();


    loadDashboard();
    loadPageUser();
    initScholarshipsPage();
    setupCollapsibleSidebar();



    function applyDarkMode(enabled) {

        document.body.classList.toggle("dark", enabled);

    }

    (async () => {

        try {

            const res = await fetch("../backend/get_settings.php");
            const json = await res.json();

            if (json.success) {
                applyDarkMode(json.data.dark_mode == 1);
            }

        } catch (e) { }

    })();

    // Only initialize Saved Scholarships
    // if the function currently exists.
    if (
        typeof initSavedScholarshipsPage === 'function'
    ) {
        initSavedScholarshipsPage();
    }

    // DASHBOARD PREMIUM FEATURES
    if (
        typeof initDashboardFeatures === 'function'
    ) {
        initDashboardFeatures();
    }

    // PERSONALIZED RECOMMENDATIONS
    if (
        typeof initRecommendationsPage === 'function'
    ) {
        initRecommendationsPage();
    }

    if (
        typeof initUpdatesPage === 'function'
    ) {
        initUpdatesPage();
    }

    if (
        typeof initQuickScholarshipSearch === 'function'
    ) {
        initQuickScholarshipSearch();
    }


});


// 1. MULTISELECT DROPDOWNS

function initMultiselects() {

    const multiselects =
        document.querySelectorAll('.multiselect');

    if (multiselects.length === 0) {
        return;
    }

    multiselects.forEach((multiselect) => {

        const trigger =
            multiselect.querySelector(
                '.multiselect-trigger'
            );

        const placeholder =
            multiselect.querySelector(
                '.multiselect-placeholder'
            );

        const checkboxes =
            multiselect.querySelectorAll(
                'input[type="checkbox"]'
            );

        if (!trigger) {
            return;
        }

        trigger.addEventListener(
            'click',
            (event) => {

                event.stopPropagation();

                multiselect.classList.toggle(
                    'multiselect--open'
                );
            }
        );

        checkboxes.forEach((checkbox) => {

            checkbox.addEventListener(
                'change',
                () => {

                    if (!placeholder) {
                        return;
                    }

                    const checkedLabels =
                        [...checkboxes]
                            .filter(
                                (checkboxItem) =>
                                    checkboxItem.checked
                            )
                            .map(
                                (checkboxItem) => {

                                    const option =
                                        checkboxItem.closest(
                                            '.multiselect-option'
                                        );

                                    return option
                                        ? option.textContent.trim()
                                        : checkboxItem.value;
                                }
                            );

                    placeholder.textContent =
                        checkedLabels.length
                            ? checkedLabels.join(', ')
                            : 'Select preferences';
                }
            );
        });
    });

    document.addEventListener(
        'click',
        (event) => {

            multiselects.forEach(
                (multiselect) => {

                    if (
                        !multiselect.contains(
                            event.target
                        )
                    ) {

                        multiselect.classList.remove(
                            'multiselect--open'
                        );
                    }
                }
            );
        }
    );
}


//2. FILE UPLOAD BOXES

function initFileUploads() {

    const uploadItems =
        document.querySelectorAll('.upload-item');

    if (uploadItems.length === 0) {
        return;
    }

    uploadItems.forEach((item) => {

        const input =
            item.querySelector(
                'input[type="file"]'
            );

        const textEl =
            item.querySelector(
                '.upload-text'
            );

        if (!input) {
            return;
        }

        const originalText =
            textEl
                ? textEl.innerHTML
                : '';

        input.addEventListener(
            'change',
            () => {

                const hasFile =
                    input.files.length > 0;

                item.classList.toggle(
                    'has-file',
                    hasFile
                );

                if (textEl) {

                    if (hasFile) {

                        textEl.textContent =
                            input.files[0].name;

                    } else {

                        textEl.innerHTML =
                            originalText;
                    }
                }
            }
        );
    });
}


// 3. FILTER PILLS

function initFilterPills() {

    const allPills =
        document.querySelectorAll(
            '.pill-btn'
        );

    if (allPills.length === 0) {
        return;
    }

    const allButton =
        [...allPills].find(
            (button) =>
                button.textContent.trim() === 'All'
        );

    allPills.forEach((pill) => {

        pill.addEventListener(
            'click',
            () => {

                if (pill === allButton) {

                    allPills.forEach(
                        (otherPill) => {

                            otherPill.classList.toggle(
                                'pill-btn--active',
                                otherPill === allButton
                            );
                        }
                    );

                    applyScholarshipFilters();
                    return;
                }

                allPills.forEach(
                    (otherPill) => {

                        otherPill.classList.remove(
                            'pill-btn--active'
                        );
                    }
                );

                pill.classList.add(
                    'pill-btn--active'
                );

                applyScholarshipFilters();
            }
        );
    });
}


// 4. SHORTLIST HEART TOGGLE

function initShortlistHearts() {

    const shortlistLinks =
        document.querySelectorAll(
            '.shortlist-link'
        );

    if (shortlistLinks.length === 0) {
        return;
    }

    shortlistLinks.forEach((link) => {

        link.addEventListener(
            'click',
            (event) => {

                event.preventDefault();

                const heart =
                    link.querySelector(
                        '.heart-icon'
                    );

                if (!heart) {
                    return;
                }

                const isSaved =
                    heart.textContent.trim() === '♥';

                heart.textContent =
                    isSaved
                        ? '♡'
                        : '♥';

                link.classList.toggle(
                    'shortlist-link--active',
                    !isSaved
                );
            }
        );
    });
}



// 5. SIGN IN / SIGN UP VALIDATION

function initAuthForms() {

    const forms =
        document.querySelectorAll(
            'body > form'
        );

    if (forms.length === 0) {
        return;
    }

    forms.forEach((form) => {

        form.addEventListener(
            'submit',
            (event) => {

                const inputs =
                    form.querySelectorAll(
                        'input[type="text"], ' +
                        'input[type="email"], ' +
                        'input[type="password"]'
                    );

                let hasEmpty = false;

                inputs.forEach((input) => {

                    input.style.borderColor = '';

                    if (
                        input.value.trim() === ''
                    ) {

                        hasEmpty = true;
                        input.style.borderColor =
                            '#e8503a';
                    }
                });

                if (hasEmpty) {

                    event.preventDefault();

                    let message =
                        form.querySelector(
                            '.form-error-message'
                        );

                    if (!message) {

                        message =
                            document.createElement(
                                'p'
                            );

                        message.className =
                            'form-error-message';

                        message.style.color =
                            '#e8503a';

                        message.style.marginTop =
                            '8px';

                        form.appendChild(
                            message
                        );
                    }

                    message.textContent =
                        'Please fill in all fields.';
                }
            }
        );
    });
}


// 6. PROFILE ROUTING

async function openProfile() {

    try {

        const response =
            await fetch(
                '../backend/check_profile_status.php',
                {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store'
                }
            );

        if (!response.ok) {

            throw new Error(
                'Could not check profile status.'
            );
        }

        const data =
            await response.json();

        if (!data.logged_in) {

            window.location.href =
                '../signin/signin.html';

            return;
        }

        if (
            data.completed === true ||
            data.next_step === 'summary'
        ) {

            window.location.href =
                '../profile/profile_summary.html';

            return;
        }

        if (
            data.next_step === 'profile2'
        ) {

            window.location.href =
                '../profile/profile2.html';

            return;
        }

        if (
            data.next_step === 'profile3'
        ) {

            window.location.href =
                '../profile/profile3.html';

            return;
        }

        window.location.href =
            '../profile/profile1.html';

    } catch (error) {

        console.error(
            'Profile routing failed:',
            error
        );

        alert(
            'Unable to check your profile.'
        );
    }
}

window.openProfile = openProfile;




// 7. PASSWORD TOGGLE

function initPasswordToggles() {

    const pwToggles =
        document.querySelectorAll(
            '.pw-toggle'
        );

    if (pwToggles.length === 0) {
        return;
    }

    pwToggles.forEach(
        (toggleBtn) => {

            toggleBtn.addEventListener(
                'click',
                () => {

                    const input =
                        toggleBtn.previousElementSibling;

                    if (!input) {
                        return;
                    }

                    if (
                        input.type === 'password'
                    ) {

                        input.type = 'text';
                        toggleBtn.textContent = '🙈';

                    } else {

                        input.type = 'password';
                        toggleBtn.textContent = '👁️';
                    }
                }
            );
        }
    );
}


// 8. DASHBOARD
async function loadDashboard() {

    const usernameElement =
        document.getElementById(
            'dashboard-username'
        );

    if (!usernameElement) {
        return;
    }

    try {

        const response =
            await fetch(
                '../backend/get_dashboard.php',
                {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store'
                }
            );

        if (!response.ok) {

            throw new Error(
                'Dashboard request failed.'
            );
        }

        const data =
            await response.json();

        if (
            !data.success ||
            !data.logged_in
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }

        const welcomeUsername =
            document.getElementById(
                'welcome-username'
            );

        usernameElement.textContent =
            data.username;

        if (welcomeUsername) {

            welcomeUsername.textContent =
                data.username;
        }

        const roleElement =
            document.getElementById(
                'dashboard-role'
            );

        if (roleElement) {

            roleElement.textContent =
                capitalizeFirstLetter(
                    data.role || 'student'
                );
        }

        const recommended =
            document.getElementById(
                'recommended-count'
            );

        if (recommended) {

            recommended.textContent =
                data.recommended_scholarships ?? 0;
        }

        const saved =
            document.getElementById(
                'saved-count'
            );

        if (saved) {

            saved.textContent =
                data.saved_scholarships ?? 0;
        }

        const applications =
            document.getElementById(
                'applications-count'
            );

        if (applications) {

            applications.textContent =
                data.applications ?? 0;
        }

        const notificationCount =
            document.getElementById(
                'notification-count'
            );

        if (notificationCount) {

            notificationCount.textContent =
                data.unread_notifications ?? 0;
        }

    } catch (error) {

        console.error(
            'Dashboard loading failed:',
            error
        );

        usernameElement.textContent =
            'User';
    }
}


// 9. LOAD USER ON SCHOLARSHIPS / OTHER HOME PAGES

async function loadPageUser() {

    const usernameElement =
        document.getElementById(
            'page-username'
        );

    if (!usernameElement) {
        return;
    }

    try {

        const response =
            await fetch(
                '../backend/get_dashboard.php',
                {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store'
                }
            );

        if (!response.ok) {

            throw new Error(
                'Unable to load user.'
            );
        }

        const data =
            await response.json();

        if (
            !data.success ||
            !data.logged_in
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }

        usernameElement.textContent =
            data.username;

        const roleElement =
            document.getElementById(
                'page-role'
            );

        if (roleElement) {

            roleElement.textContent =
                capitalizeFirstLetter(
                    data.role || 'student'
                );
        }

        const notificationCount =
            document.getElementById(
                'notification-count'
            );

        if (notificationCount) {

            notificationCount.textContent =
                data.unread_notifications ?? 0;
        }

    } catch (error) {

        console.error(
            'Page user loading failed:',
            error
        );

        usernameElement.textContent =
            'User';
    }
}


// 10. SCHOLARSHIPS PAGE

let allScholarships = [];


function initScholarshipsPage() {

    const resultsContainer =
        document.getElementById(
            'scholarship-results'
        );

    if (!resultsContainer) {
        return;
    }

    loadScholarships();

    showScholarshipPostMessage();


    const searchInput =
        document.getElementById(
            'global-search'
        );

    const destination =
        document.getElementById(
            'destination'
        );

    const applyButton =
        document.getElementById(
            'apply-scholarship-filters'
        );

    const fullOnly =
        document.getElementById(
            'full-scholarships-only'
        );

    const internationalOnly =
        document.getElementById(
            'international-students'
        );

    const exchangeOnly =
        document.getElementById(
            'exchange-programs'
        );


    if (searchInput) {

        searchInput.addEventListener(
            'input',
            applyScholarshipFilters
        );
    }
    const globalSearchButton =
        document.getElementById(
            'global-search-btn'
        );

    if (globalSearchButton) {

        globalSearchButton.addEventListener(
            'click',
            applyScholarshipFilters
        );
    }
    if (searchInput) {

        searchInput.addEventListener(
            'keydown',
            event => {

                if (event.key === 'Enter') {

                    event.preventDefault();

                    applyScholarshipFilters();
                }
            }
        );
    }

    if (destination) {

        destination.addEventListener(
            'change',
            applyScholarshipFilters
        );
    }

    if (applyButton) {

        applyButton.addEventListener(
            'click',
            applyScholarshipFilters
        );
    }

    if (fullOnly) {

        fullOnly.addEventListener(
            'change',
            applyScholarshipFilters
        );
    }

    if (internationalOnly) {

        internationalOnly.addEventListener(
            'change',
            applyScholarshipFilters
        );
    }

    if (exchangeOnly) {

        exchangeOnly.addEventListener(
            'change',
            applyScholarshipFilters
        );
    }
}




// 11. FETCH SCHOLARSHIPS FROM DATABASE

async function loadScholarships() {

    const resultsContainer =
        document.getElementById(
            'scholarship-results'
        );

    if (!resultsContainer) {
        return;
    }

    resultsContainer.innerHTML =
        '<p>Loading scholarships...</p>';

    try {

        const response =
            await fetch(
                '../backend/get_scholarships.php',
                {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store'
                }
            );

        if (!response.ok) {

            throw new Error(
                'Scholarship request failed: ' +
                response.status
            );
        }

        const data =
            await response.json();

        console.log(
            'SCHOLARSHIPS FROM DATABASE:',
            data
        );

        if (
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }

        if (!data.success) {

            throw new Error(
                data.message ||
                'Failed to load scholarships.'
            );
        }

        allScholarships =
            Array.isArray(
                data.scholarships
            )
                ? data.scholarships
                : [];

        displayScholarships(
            allScholarships
        );

    } catch (error) {

        console.error(
            'Scholarship loading error:',
            error
        );

        resultsContainer.innerHTML = `
            <div class="no-scholarships">
                <p>
                    Unable to load scholarships.
                </p>
            </div>
        `;
    }
}


// 12. DISPLAY SCHOLARSHIP CARDS

function displayScholarships(
    scholarships
) {

    const resultsContainer =
        document.getElementById(
            'scholarship-results'
        );

    if (!resultsContainer) {
        return;
    }

    resultsContainer.innerHTML = '';

    if (
        !Array.isArray(scholarships) ||
        scholarships.length === 0
    ) {

        resultsContainer.innerHTML = `
            <div class="no-scholarships">
                <p>
                    No scholarships found.
                </p>
            </div>
        `;

        return;
    }

    scholarships.forEach(
        (scholarship) => {

            const card =
                document.createElement(
                    'article'
                );

            card.className =
                'result-card';


            const fundingLabel =
                formatFundingType(
                    scholarship.funding_type
                );


            const deadlineLabel =
                formatScholarshipDate(
                    scholarship.deadline
                );


            const ieltsLabel =
                Number(
                    scholarship.ielts_required
                ) === 1
                    ? 'Required'
                    : 'Not required';


            const statusLabel =
                scholarship.verification_status
                    ? capitalizeFirstLetter(
                        scholarship.verification_status
                    )
                    : '';


            const benefits =
                Array.isArray(
                    scholarship.benefits
                )
                    ? scholarship.benefits
                        .map(
                            (benefit) =>
                                formatBenefitType(
                                    benefit.benefit_type
                                )
                        )
                        .filter(Boolean)
                    : [];


            card.innerHTML = `

                <div class="result-card-content">

                    <div class="result-header">

                        <div>

                            <h3 class="result-title">
                                ${escapeHtml(
                scholarship.title ||
                'Untitled scholarship'
            )}
                            </h3>

                            <p class="result-provider">
                                ${escapeHtml(
                scholarship.provider ||
                'Provider not specified'
            )}
                            </p>

                        </div>

                        <span class="funding-badge">

                            ${escapeHtml(
                fundingLabel
            )}

                        </span>

                    </div>


                    <p class="result-description">

                        ${escapeHtml(
                scholarship.description ||
                'No description provided.'
            )}

                    </p>


                    <div class="scholarship-details">

                        <p>
                            <strong>
                                Country:
                            </strong>

                            ${escapeHtml(
                scholarship.country ||
                'Not specified'
            )}
                        </p>


                        <p>
                            <strong>
                                University:
                            </strong>

                            ${escapeHtml(
                scholarship.university ||
                'Not specified'
            )}
                        </p>


                        <p>
                            <strong>
                                Education level:
                            </strong>

                            ${escapeHtml(
                scholarship.education_level ||
                'Not specified'
            )}
                        </p>


                        <p>
                            <strong>
                                Eligible courses:
                            </strong>

                            ${escapeHtml(
                scholarship.eligible_courses ||
                'Not specified'
            )}
                        </p>


                        <p>
                            <strong>
                                Minimum GPA:
                            </strong>

                            ${escapeHtml(
                scholarship.minimum_gpa ??
                'Not specified'
            )}
                        </p>


                        <p>
                            <strong>
                                Eligible nationalities:
                            </strong>

                            ${escapeHtml(
                scholarship.eligible_nationalities ||
                'Not specified'
            )}
                        </p>


                        <p>
                            <strong>
                                Age limit:
                            </strong>

                            ${escapeHtml(
                scholarship.age_limit ??
                'Not specified'
            )}
                        </p>


                        <p>
                            <strong>
                                IELTS:
                            </strong>

                            ${escapeHtml(
                ieltsLabel
            )}
                        </p>


                        <p>
                            <strong>
                                Duration:
                            </strong>

                            ${escapeHtml(
                scholarship.duration ||
                'Not specified'
            )}
                        </p>


                        <p>
                            <strong>
                                Deadline:
                            </strong>

                            ${escapeHtml(
                deadlineLabel
            )}
                        </p>


                        ${benefits.length > 0
                    ? `
                                <p>
                                    <strong>
                                        Benefits:
                                    </strong>

                                    ${escapeHtml(
                        benefits.join(', ')
                    )}
                                </p>
                                `
                    : ''
                }


                        ${statusLabel
                    ? `
                                <p>
                                    <strong>
                                        Status:
                                    </strong>

                                    ${escapeHtml(
                        statusLabel
                    )}
                                </p>
                                `
                    : ''
                }

                    </div>


                    <div class="result-actions">

                        <button
                            type="button"
                            class="save-scholarship-btn"
                            data-scholarship-id="${Number(
                    scholarship.scholarship_id
                )}"
                        >
                            Save
                        </button>


                        ${scholarship.application_link
                    ? `
                                <a
                                    href="${escapeHtml(
                        scholarship.application_link
                    )}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="apply-scholarship-btn"
                                >
                                    Apply
                                </a>
                                `
                    : ''
                }

                    </div>

                </div>
            `;


            const saveButton =
                card.querySelector(
                    '.save-scholarship-btn'
                );


            if (saveButton) {

                saveButton.addEventListener(
                    'click',
                    async () => {

                        await saveScholarship(
                            scholarship.scholarship_id,
                            saveButton
                        );

                    }
                );
            }


            resultsContainer.appendChild(
                card
            );
        }
    );
}


// 13. SCHOLARSHIP FILTERING

function applyScholarshipFilters() {

    let filtered =
        [...allScholarships];


    const searchInput =
        document.getElementById(
            'global-search'
        );


    const destination =
        document.getElementById(
            'destination'
        );


    const fullOnly =
        document.getElementById(
            'full-scholarships-only'
        );


    const internationalOnly =
        document.getElementById(
            'international-students'
        );


    const exchangeOnly =
        document.getElementById(
            'exchange-programs'
        );


    if (
        searchInput &&
        searchInput.value.trim() !== ''
    ) {

        const search =
            searchInput.value
                .trim()
                .toLowerCase();


        filtered =
            filtered.filter(
                (scholarship) => {

                    const title =
                        (
                            scholarship.title ||
                            ''
                        ).toLowerCase();


                    const provider =
                        (
                            scholarship.provider ||
                            ''
                        ).toLowerCase();


                    const description =
                        (
                            scholarship.description ||
                            ''
                        ).toLowerCase();


                    return (
                        title.includes(search) ||
                        provider.includes(search) ||
                        description.includes(search)
                    );
                }
            );
    }


    if (
        destination &&
        destination.value !== ''
    ) {

        const selectedCountry =
            destination.value
                .trim()
                .toLowerCase();


        filtered =
            filtered.filter(
                (scholarship) => {

                    const country =
                        (
                            scholarship.country ||
                            ''
                        )
                            .trim()
                            .toLowerCase();


                    return (
                        country === selectedCountry ||
                        country.includes(
                            selectedCountry
                        )
                    );
                }
            );
    }


    const activeFunding =
        document.querySelector(
            '.pill-btn--active[data-funding]'
        );


    if (
        activeFunding &&
        activeFunding.dataset.funding
    ) {

        const funding =
            activeFunding.dataset.funding;


        filtered =
            filtered.filter(
                (scholarship) =>
                    scholarship.funding_type ===
                    funding
            );
    }


    if (
        fullOnly &&
        fullOnly.checked
    ) {

        filtered =
            filtered.filter(
                (scholarship) =>
                    scholarship.funding_type ===
                    'fully_funded'
            );
    }


    if (
        internationalOnly &&
        internationalOnly.checked
    ) {

        filtered =
            filtered.filter(
                (scholarship) => {

                    const nationalities =
                        (
                            scholarship
                                .eligible_nationalities ||
                            ''
                        ).toLowerCase();


                    return (
                        nationalities.includes(
                            'international'
                        ) ||
                        nationalities.includes(
                            'all'
                        ) ||
                        nationalities.includes(
                            'over'
                        )
                    );
                }
            );
    }


    if (
        exchangeOnly &&
        exchangeOnly.checked
    ) {

        filtered =
            filtered.filter(
                (scholarship) => {

                    const courses =
                        (
                            scholarship
                                .eligible_courses ||
                            ''
                        ).toLowerCase();


                    const description =
                        (
                            scholarship.description ||
                            ''
                        ).toLowerCase();


                    return (
                        courses.includes(
                            'exchange'
                        ) ||
                        description.includes(
                            'exchange'
                        )
                    );
                }
            );
    }


    displayScholarships(
        filtered
    );
}


// 14. SAVE SCHOLARSHIP

async function saveScholarship(
    scholarshipId,
    button = null
) {

    try {

        if (button) {

            button.disabled = true;

            button.textContent =
                'Saving...';
        }


        const response =
            await fetch(
                '../backend/save_scholarship.php',
                {
                    method: 'POST',

                    credentials: 'include',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    body: JSON.stringify({
                        scholarship_id:
                            Number(
                                scholarshipId
                            )
                    })
                }
            );


        // Read response

        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (error) {

            console.error(
                'Invalid PHP response:',
                responseText
            );

            throw new Error(
                'The server returned an invalid response.'
            );
        }


        console.log(
            'SAVE SCHOLARSHIP RESPONSE:',
            data
        );


        // LOGIN

        if (
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return false;
        }


        // if error
        if (
            !response.ok ||
            !data.success
        ) {

            throw new Error(
                data.message ||
                'Unable to save scholarship.'
            );
        }


        //if success

        if (button) {

            button.textContent =
                '✓ Saved';

            button.disabled =
                true;
        }


        showScholarshipMessage(
            data.message ||
            'Scholarship saved successfully.',
            'success'
        );


        return true;


    } catch (error) {

        console.error(
            'Save scholarship failed:',
            error
        );


        if (button) {

            button.disabled =
                false;

            button.textContent =
                '♥ Save';
        }


        showScholarshipMessage(
            error.message ||
            'Unable to save scholarship.',
            'error'
        );


        return false;
    }
}

// 15. SCHOLARSHIP POST SUCCESS MESSAGE

function showScholarshipPostMessage() {

    const params =
        new URLSearchParams(
            window.location.search
        );

    const posted =
        params.get('posted');


    if (posted === '1') {

        showScholarshipMessage(
            'Scholarship posted successfully.',
            'success'
        );
    }


    if (posted === 'pending') {

        showScholarshipMessage(
            'Scholarship submitted successfully and is awaiting approval.',
            'success'
        );
    }
}


// 16. SCHOLARSHIP MESSAGE

function showScholarshipMessage(
    message,
    type = 'success'
) {

    const messageBox =
        document.getElementById(
            'scholarship-message'
        );


    if (!messageBox) {
        return;
    }


    messageBox.textContent =
        message;


    messageBox.style.display =
        'block';


    messageBox.classList.remove(
        'scholarship-message--success',
        'scholarship-message--error'
    );


    messageBox.classList.add(
        type === 'error'
            ? 'scholarship-message--error'
            : 'scholarship-message--success'
    );


    window.setTimeout(
        () => {

            messageBox.style.display =
                'none';

        },
        5000
    );
}


//17. FORMAT FUNDING TYPE

function formatFundingType(type) {

    switch (type) {

        case 'fully_funded':
            return 'Fully Funded';

        case 'partially_funded':
            return 'Partially Funded';

        case 'tuition_only':
            return 'Tuition Only';

        default:

            return type
                ? String(type)
                    .replaceAll(
                        '_',
                        ' '
                    )
                : 'Not specified';
    }
}


// 18. FORMAT BENEFITS

function formatBenefitType(type) {

    switch (type) {

        case 'tuition':
            return 'Tuition';

        case 'accommodation':
            return 'Accommodation';

        case 'monthly_stipend':
            return 'Monthly stipend';

        case 'airfare':
            return 'Airfare';

        case 'medical_insurance':
            return 'Medical insurance';

        case 'books':
        case 'books_allowance':
            return 'Books / research allowance';

        default:

            return type
                ? String(type)
                    .replaceAll(
                        '_',
                        ' '
                    )
                : '';
    }
}


//19. FORMAT DATE

function formatScholarshipDate(
    dateValue
) {

    if (!dateValue) {
        return 'Not specified';
    }


    const date =
        new Date(
            dateValue +
            'T00:00:00'
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
            month: 'short',
            day: 'numeric'
        }
    );
}


// 20. CAPITALIZE FIRST LETTER

function capitalizeFirstLetter(text) {

    if (!text) {
        return '';
    }

    return (
        text.charAt(0).toUpperCase() +
        text.slice(1)
    );
}


// 21. ESCAPE HTML

function escapeHtml(value) {

    if (
        value === null ||
        value === undefined
    ) {

        return '';
    }


    return String(value)

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


// MAKE FUNCTIONS AVAILABLE TO HTML

window.openProfile =
    openProfile;

window.loadScholarships =
    loadScholarships;

window.applyScholarshipFilters =
    applyScholarshipFilters;

window.saveScholarship =
    saveScholarship;

// DASHBOARD PREMIUM FEATURES

function initDashboardFeatures() {

    const matchingCard =
        document.getElementById('scholarship-matching-card');

    const exportCard =
        document.getElementById('pdf-export-card');

    const communityButton =
        document.getElementById('community-btn');

    // Not dashboard.html
    if (
        !matchingCard &&
        !exportCard &&
        !communityButton
    ) {
        return;
    }

    // Student Community
    if (communityButton) {
        communityButton.addEventListener(
            'click',
            openStudentCommunity
        );
    }

    // Check whether matching/PDF should be unlocked
    checkDashboardProfileAccess();
}


// CHECK DASHBOARD PROFILE ACCESS

async function checkDashboardProfileAccess() {

    const matchingButton =
        document.getElementById('view-scholarships-btn');

    const exportButton =
        document.getElementById('export-btn');

    const matchingStatus =
        document.getElementById('matching-status');

    const exportStatus =
        document.getElementById('export-status');

    try {

        const response =
            await fetch(
                '../backend/check_profile_status.php',
                {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store'
                }
            );

        if (!response.ok) {
            throw new Error(
                'Profile check failed: ' +
                response.status
            );
        }

        const data =
            await response.json();

        console.log(
            'PROFILE ACCESS:',
            data
        );

        // Session expired
        if (data.logged_in === false) {

            window.location.href =
                '../signin/signin.html';

            return;
        }

        if (!data.success) {

            throw new Error(
                data.message ||
                'Unable to check profile.'
            );
        }

        // Profile complete
        if (data.profile_complete === true) {

            unlockDashboardFeatures();
            return;
        }

        // Profile incomplete
        lockDashboardFeatures(
            data.missing_fields || []
        );

    } catch (error) {

        console.error(
            'Dashboard feature access error:',
            error
        );

        // Fail closed if backend verification fails
        if (matchingButton) {

            matchingButton.disabled = true;

            matchingButton.textContent =
                'Unable to Verify Profile';
        }

        if (exportButton) {

            exportButton.disabled = true;

            exportButton.textContent =
                'Unable to Verify Profile';
        }

        if (matchingStatus) {

            matchingStatus.textContent =
                'Profile verification unavailable.';
        }

        if (exportStatus) {

            exportStatus.textContent =
                'Profile verification unavailable.';
        }
    }
}


// UNLOCK DASHBOARD FEATURES
function unlockDashboardFeatures() {

    const matchingCard =
        document.getElementById('scholarship-matching-card');

    const exportCard =
        document.getElementById('pdf-export-card');

    const matchingButton =
        document.getElementById('view-scholarships-btn');

    const exportButton =
        document.getElementById('export-btn');

    const matchingStatus =
        document.getElementById('matching-status');

    const exportStatus =
        document.getElementById('export-status');


    // Scholarship Matching
    if (matchingCard) {

        matchingCard.classList.remove(
            'feature-card--locked'
        );
    }

    if (matchingStatus) {

        matchingStatus.className =
            'feature-status feature-status--available';

        matchingStatus.textContent =
            '✓ Unlocked — your profile is complete';
    }

    if (matchingButton) {

        matchingButton.disabled = false;

        matchingButton.textContent =
            'View My Recommendations';

        matchingButton.onclick =
            function () {

                window.location.href =
                    'recommendations.html';
            };
    }


    // PDF Report
    if (exportCard) {

        exportCard.classList.remove(
            'feature-card--locked'
        );
    }

    if (exportStatus) {

        exportStatus.className =
            'feature-status feature-status--available';

        exportStatus.textContent =
            '✓ Unlocked — reports are available';
    }

    if (exportButton) {

        exportButton.disabled = false;

        exportButton.textContent =
            'Build Scholarship Report';

        exportButton.onclick =
            function () {

                window.location.href =
                    'export.html';
            };
    }
}


//LOCK DASHBOARD FEATURES

function lockDashboardFeatures(
    missingFields = []
) {

    const matchingCard =
        document.getElementById('scholarship-matching-card');

    const exportCard =
        document.getElementById('pdf-export-card');

    const matchingButton =
        document.getElementById('view-scholarships-btn');

    const exportButton =
        document.getElementById('export-btn');

    const matchingStatus =
        document.getElementById('matching-status');

    const exportStatus =
        document.getElementById('export-status');


    if (matchingCard) {

        matchingCard.classList.add(
            'feature-card--locked'
        );
    }

    if (exportCard) {

        exportCard.classList.add(
            'feature-card--locked'
        );
    }


    let message =
        '🔒 Complete your profile to unlock this feature.';

    if (
        Array.isArray(missingFields) &&
        missingFields.length > 0
    ) {

        message =
            '🔒 Profile incomplete. Missing: ' +
            missingFields.join(', ');
    }

    if (matchingStatus) {

        matchingStatus.className =
            'feature-status feature-status--locked';

        matchingStatus.textContent =
            message;
    }

    if (exportStatus) {

        exportStatus.className =
            'feature-status feature-status--locked';

        exportStatus.textContent =
            '🔒 Complete your profile to unlock PDF reports.';
    }


    // Matching button sends student to profile
    if (matchingButton) {

        matchingButton.disabled = false;

        matchingButton.textContent =
            'Complete Profile';

        matchingButton.onclick =
            function () {

                openProfile();
            };
    }


    // Export button also sends student to profile
    if (exportButton) {

        exportButton.disabled = false;

        exportButton.textContent =
            'Complete Profile';

        exportButton.onclick =
            function () {

                openProfile();
            };
    }
}


// STUDENT COMMUNITY

function openStudentCommunity() {

    /*
       Replace this with the real Discord
       invitation when we create/connect it.
    */

    const discordInvite =
        'https://discord.gg/THNBweKWq';


    window.open(
        discordInvite,
        '_blank',
        'noopener,noreferrer'
    );
}


// SCHOLARSHIP RECOMMENDATIONS

let scholarshipRecommendations = [];


// INITIALIZE RECOMMENDATIONS PAGE

function initRecommendationsPage() {

    const container =
        document.getElementById(
            'recommendation-results'
        );

    // Not recommendations.html
    if (!container) {
        return;
    }

    loadRecommendations();

    const searchInput =
        document.getElementById(
            'recommendation-search'
        );

    const searchButton =
        document.getElementById(
            'recommendation-search-btn'
        );

    const sortSelect =
        document.getElementById(
            'recommendation-sort'
        );

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterRecommendations
        );
    }

    if (searchButton) {

        searchButton.addEventListener(
            'click',
            filterRecommendations
        );
    }

    if (sortSelect) {

        sortSelect.addEventListener(
            'change',
            filterRecommendations
        );
    }
}



// LOAD RECOMMENDATIONS

async function loadRecommendations() {

    const container =
        document.getElementById(
            'recommendation-results'
        );


    if (!container) {
        return;
    }


    container.innerHTML =
        '<p>Calculating scholarship matches...</p>';


    try {

        const response =
            await fetch(
                '../backend/get_recommendations.php',
                {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        if (!response.ok) {

            throw new Error(
                'Recommendation request failed: ' +
                response.status
            );

        }


        const data =
            await response.json();


        console.log(
            'RECOMMENDATIONS:',
            data
        );


        // if not logged in

        if (
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return;

        }



        // if profile is incomplete

        if (
            data.profile_complete === false
        ) {

            displayIncompleteRecommendationProfile(
                data
            );

            return;

        }



        //error message

        if (!data.success) {

            throw new Error(
                data.message ||
                'Unable to load recommendations.'
            );

        }



        //store results

        scholarshipRecommendations =
            Array.isArray(
                data.recommendations
            )
                ? data.recommendations
                : [];



        //PROFILE SUMMARY


        displayMatchingProfile(
            data.student
        );



        //scholarships

        displayRecommendations(
            scholarshipRecommendations
        );


    }
    catch (error) {

        console.error(
            'Recommendation loading error:',
            error
        );


        container.innerHTML = `

            <article class="update-card">

                <h3 class="update-title">
                    Unable to load recommendations
                </h3>

                <p class="update-text">
                    Please refresh the page and try again.
                </p>

            </article>

        `;

    }

}



// DISPLAY PROFILE USED FOR MATCHING

function displayMatchingProfile(
    student
) {

    const container =
        document.getElementById(
            'matching-profile-details'
        );


    if (
        !container ||
        !student
    ) {
        return;
    }


    container.innerHTML = `

        <div class="recommendation-profile-item">

            <strong>
                Education
            </strong>

            <span>
                ${escapeHtml(
        student.education_level || 'Not specified'
    )}
            </span>

        </div>


        <div class="recommendation-profile-item">

            <strong>
                Course
            </strong>

            <span>
                ${escapeHtml(
        student.course || 'Not specified'
    )}
            </span>

        </div>


        <div class="recommendation-profile-item">

            <strong>
                GPA
            </strong>

            <span>
                ${escapeHtml(
        String(
            student.gpa ?? 'Not specified'
        )
    )}
            </span>

        </div>


        <div class="recommendation-profile-item">

            <strong>
                Nationality
            </strong>

            <span>
                ${escapeHtml(
        student.nationality || 'Not specified'
    )}
            </span>

        </div>


        <div class="recommendation-profile-item">

            <strong>
                Preferred Country
            </strong>

            <span>
                ${escapeHtml(
        student.preferred_country || 'Any'
    )}
            </span>

        </div>


        <div class="recommendation-profile-item">

            <strong>
                Funding
            </strong>

            <span>
                ${escapeHtml(
        formatFundingType(
            student.funding_preference
        )
    )}
            </span>

        </div>

    `;

}



// DISPLAY RECOMMENDATIONS

function displayRecommendations(
    recommendations
) {

    const container =
        document.getElementById(
            'recommendation-results'
        );


    const countText =
        document.getElementById(
            'recommendation-count-text'
        );


    if (!container) {
        return;
    }


    container.innerHTML = '';


    if (countText) {

        countText.textContent =
            `${recommendations.length} matching scholarship${recommendations.length === 1
                ? ''
                : 's'
            } found`;

    }



    // if there are no matches

    if (
        !recommendations ||
        recommendations.length === 0
    ) {

        container.innerHTML = `

            <article class="update-card">

                <h3 class="update-title">
                    No strong matches found
                </h3>

                <p class="update-text">

                    We couldn't find scholarships that
                    strongly match your current profile.

                    You can still browse all available
                    scholarships.

                </p>


                <div class="update-actions">

                    <a
                        href="scholarships.html"
                        class="update-btn"
                    >
                        Browse All Scholarships
                    </a>

                </div>

            </article>

        `;

        return;

    }



    // CREATE CARDS

    recommendations.forEach(
        (scholarship) => {


            const card =
                document.createElement(
                    'article'
                );


            card.className =
                'update-card recommendation-card';



            // SEARCH DATA

            card.dataset.search = `

                ${scholarship.title || ''}

                ${scholarship.provider || ''}

                ${scholarship.country || ''}

                ${scholarship.university || ''}

                ${scholarship.funding_type || ''}

            `.toLowerCase();



            // MATCH REASONS

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
                reasons
                    .map(
                        reason => `

                            <li class="match-reason">
                                ✓ ${escapeHtml(reason)}
                            </li>

                        `
                    )
                    .join('');


            const warningsHtml =
                warnings
                    .map(
                        warning => `

                            <li class="match-warning">
                                ⚠ ${escapeHtml(warning)}
                            </li>

                        `
                    )
                    .join('');



            // CARD

            card.innerHTML = `

                <div class="recommendation-card-head">


                    <div>

                        <h3 class="update-title">

                            ${escapeHtml(
                scholarship.title ||
                'Untitled Scholarship'
            )}

                        </h3>


                        <p class="update-text">

                            ${escapeHtml(
                scholarship.provider ||
                'Provider not specified'
            )}

                        </p>

                    </div>



                    <div class="match-score">

                        <strong>

                            ${Number(
                scholarship.match_score
            )}%

                        </strong>

                        <span>

                            ${escapeHtml(
                scholarship.match_label ||
                'Match'
            )}

                        </span>

                    </div>


                </div>



                <p class="update-text">

                    ${escapeHtml(
                scholarship.description ||
                'No description provided.'
            )}

                </p>



                <div class="recommendation-meta">


                    <span>

                        <strong>
                            Country:
                        </strong>

                        ${escapeHtml(
                scholarship.country ||
                'Not specified'
            )}

                    </span>


                    <span>

                        <strong>
                            University:
                        </strong>

                        ${escapeHtml(
                scholarship.university ||
                'Not specified'
            )}

                    </span>


                    <span>

                        <strong>
                            Funding:
                        </strong>

                        ${escapeHtml(
                formatFundingType(
                    scholarship.funding_type
                )
            )}

                    </span>


                    <span>

                        <strong>
                            Deadline:
                        </strong>

                        ${escapeHtml(
                scholarship.deadline
                    ? formatScholarshipDate(
                        scholarship.deadline
                    )
                    : 'Not specified'
            )}

                    </span>


                </div>



                <div class="match-details">


                    <div>

                        <strong>
                            Why it matches
                        </strong>

                        <ul>
                            ${reasonsHtml}
                        </ul>

                    </div>


                    ${warnings.length > 0
                    ? `

                            <div>

                                <strong>
                                    Check before applying
                                </strong>

                                <ul>
                                    ${warningsHtml}
                                </ul>

                            </div>

                            `
                    : ''
                }


                </div>



                <div class="update-actions">


                    <button
                        type="button"
                        class="update-btn save-recommendation-btn"
                    >
                        ♥ Save
                    </button>


                    ${scholarship.application_link
                    ? `
                            <a
                                href="${escapeHtml(
                        scholarship.application_link
                    )}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="update-btn"
                            >
                                Apply Now
                            </a>

                            `
                    : ''
                }


                </div>

            `;



            //save button

            const saveButton =
                card.querySelector(
                    '.save-recommendation-btn'
                );


            {

                saveButton.addEventListener(
                    'click',
                    async () => {


                        await saveScholarship(
                            scholarship.scholarship_id
                        );


                        saveButton.textContent =
                            '✓ Saved';


                        saveButton.disabled =
                            true;

                    }
                );

            }


            container.appendChild(
                card
            );

        }
    );

}



// FILTER / SORT RECOMMENDATIONS

function filterRecommendations() {

    const searchInput =
        document.getElementById(
            'recommendation-search'
        );


    const sortSelect =
        document.getElementById(
            'recommendation-sort'
        );


    const search =
        searchInput
            ? searchInput.value
                .trim()
                .toLowerCase()
            : '';


    let filtered =
        scholarshipRecommendations.filter(
            scholarship => {


                const searchable = `

                    ${scholarship.title || ''}

                    ${scholarship.provider || ''}

                    ${scholarship.country || ''}

                    ${scholarship.university || ''}

                    ${scholarship.funding_type || ''}

                `.toLowerCase();


                return searchable.includes(
                    search
                );

            }
        );



    const sort =
        sortSelect
            ? sortSelect.value
            : 'match';



    //sort

    if (sort === 'match') {

        filtered.sort(
            (a, b) =>
                Number(b.match_score) -
                Number(a.match_score)
        );

    }


    else if (sort === 'deadline') {

        filtered.sort(
            (a, b) => {

                if (!a.deadline) {
                    return 1;
                }

                if (!b.deadline) {
                    return -1;
                }

                return (
                    new Date(a.deadline) -
                    new Date(b.deadline)
                );

            }
        );

    }


    else if (sort === 'country') {

        filtered.sort(
            (a, b) =>

                String(
                    a.country || ''
                ).localeCompare(
                    String(
                        b.country || ''
                    )
                )

        );

    }


    displayRecommendations(
        filtered
    );

}



//incomplete profile message for recommendations

function displayIncompleteRecommendationProfile(
    data
) {

    const container =
        document.getElementById(
            'recommendation-results'
        );


    const profileContainer =
        document.getElementById(
            'matching-profile-details'
        );


    const missing =
        Array.isArray(
            data.missing_fields
        )
            ? data.missing_fields
            : [];


    if (profileContainer) {

        profileContainer.innerHTML = `

            <p>
                🔒 Complete your profile before using
                personalized scholarship matching.
            </p>

        `;

    }


    if (container) {

        container.innerHTML = `

            <article class="update-card">

                <h3 class="update-title">
                    Profile Required
                </h3>


                <p class="update-text">

                    Scholarship matching uses your
                    qualifications and preferences.

                </p>


                ${missing.length > 0
                ? `

                        <p class="update-text">

                            <strong>
                                Missing:
                            </strong>

                            ${escapeHtml(
                    missing.join(', ')
                )}

                        </p>

                        `
                : ''
            }


                <div class="update-actions">

                    <button
                        type="button"
                        class="update-btn"
                        onclick="openProfile()"
                    >
                        Complete Profile
                    </button>

                </div>

            </article>

        `;

    }

}

// UPDATES PAGE

let allApplicationUpdates = [];

let allNotificationUpdates = [];


// INITIALIZE UPDATES PAGE

function initUpdatesPage() {

    const applicationContainer =
        document.getElementById(
            'application-updates'
        );

    const notificationContainer =
        document.getElementById(
            'notification-updates'
        );


    // Not updates.html

    if (
        !applicationContainer &&
        !notificationContainer
    ) {

        return;
    }


    //Load database data

    loadUpdates();


    // Refresh

    const refreshButton =
        document.getElementById(
            'refresh-updates-btn'
        );


    if (refreshButton) {

        refreshButton.addEventListener(
            'click',
            () => {

                loadUpdates();

            }
        );
    }


    //search 

    const searchInput =
        document.getElementById(
            'updates-search'
        );


    const searchButton =
        document.getElementById(
            'updates-search-btn'
        );


    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterUpdates
        );
    }


    if (searchButton) {

        searchButton.addEventListener(
            'click',
            filterUpdates
        );
    }
}


//LOAD UPDATES

async function loadUpdates() {

    const applicationContainer =
        document.getElementById(
            'application-updates'
        );


    const notificationContainer =
        document.getElementById(
            'notification-updates'
        );


    if (applicationContainer) {

        applicationContainer.innerHTML =
            '<p>Loading application updates...</p>';
    }


    if (notificationContainer) {

        notificationContainer.innerHTML =
            '<p>Loading notifications...</p>';
    }


    try {

        const response =
            await fetch(
                '../backend/get_updates.php',
                {
                    method: 'GET',

                    credentials:
                        'include',

                    cache:
                        'no-store'
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (error) {

            console.error(
                'GET UPDATES PHP RESPONSE:',
                responseText
            );

            throw new Error(
                'Updates endpoint returned invalid JSON.'
            );
        }


        console.log(
            'UPDATES:',
            data
        );


        // LOGIN

        if (
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }


        // ERROR

        if (
            !response.ok ||
            !data.success
        ) {

            throw new Error(
                data.message ||
                'Unable to load updates.'
            );
        }


        // STORE DATA

        allApplicationUpdates =
            Array.isArray(
                data.applications
            )
                ? data.applications
                : [];


        allNotificationUpdates =
            Array.isArray(
                data.notifications
            )
                ? data.notifications
                : [];


        // DISPLAY
        displayApplicationUpdates(
            allApplicationUpdates
        );


        displayNotificationUpdates(
            allNotificationUpdates
        );


        // NOTIFICATION BADGE 
        const notificationCount =
            document.getElementById(
                'notification-count'
            );


        if (notificationCount) {

            notificationCount.textContent =
                Number(
                    data.unread_count ??
                    0
                );
        }


        showUpdatesMessage(
            'Updates refreshed successfully.',
            'success',
            true
        );


    } catch (error) {

        console.error(
            'Updates loading failed:',
            error
        );


        if (applicationContainer) {

            applicationContainer.innerHTML = `

                <article class="update-card">

                    <h3 class="update-title">
                        Unable to load applications
                    </h3>

                    <p class="update-text">
                        ${escapeHtml(
                error.message
            )}
                    </p>

                </article>
            `;
        }


        if (notificationContainer) {

            notificationContainer.innerHTML = `

                <article class="update-card">

                    <h3 class="update-title">
                        Unable to load notifications
                    </h3>

                    <p class="update-text">
                        Please refresh and try again.
                    </p>

                </article>
            `;
        }


        showUpdatesMessage(
            error.message ||
            'Unable to load updates.',
            'error'
        );
    }
}


// ISPLAY APPLICATION UPDATES

function displayApplicationUpdates(
    applications
) {

    const container =
        document.getElementById(
            'application-updates'
        );


    if (!container) {
        return;
    }


    container.innerHTML = '';


    if (
        !Array.isArray(applications) ||
        applications.length === 0
    ) {

        container.innerHTML = `

            <article class="update-card">

                <h3 class="update-title">
                    No application updates
                </h3>

                <p class="update-text">

                    You have not started or
                    submitted any scholarship
                    applications yet.

                </p>

            </article>
        `;

        return;
    }


    applications.forEach(
        application => {

            const card =
                document.createElement(
                    'article'
                );


            card.className =
                'update-card';


            const status =
                application.status ||
                'Not Applied';


            card.innerHTML = `

                <h3 class="update-title">

                    ${escapeHtml(
                application.scholarship_title ||
                'Scholarship Application'
            )}

                </h3>


                <p class="update-text">

                    <strong>
                        Provider:
                    </strong>

                    ${escapeHtml(
                application.provider ||
                'Not specified'
            )}

                </p>


                <p class="update-text">

                    <strong>
                        Status:
                    </strong>

                    ${escapeHtml(
                status
            )}

                </p>


                ${application.country
                    ? `

                        <p class="update-text">

                            <strong>
                                Country:
                            </strong>

                            ${escapeHtml(
                        application.country
                    )}

                        </p>

                        `
                    : ''
                }


                ${application.notes
                    ? `

                        <p class="update-text">

                            <strong>
                                Notes:
                            </strong>

                            ${escapeHtml(
                        application.notes
                    )}

                        </p>

                        `
                    : ''
                }


                <div class="result-footer">

                    Last updated:
                    ${escapeHtml(
                    formatUpdateDate(
                        application.updated_at
                    )
                )}

                </div>

            `;


            container.appendChild(
                card
            );
        }
    );
}


// DISPLAY NOTIFICATIONS

function displayNotificationUpdates(
    notifications
) {

    const container =
        document.getElementById(
            'notification-updates'
        );


    if (!container) {
        return;
    }


    container.innerHTML = '';


    if (
        !Array.isArray(notifications) ||
        notifications.length === 0
    ) {

        container.innerHTML = `

            <article class="update-card">

                <h3 class="update-title">
                    No notifications
                </h3>

                <p class="update-text">

                    You do not have any
                    notifications yet.

                </p>

            </article>
        `;

        return;
    }


    notifications.forEach(
        notification => {

            const card =
                document.createElement(
                    'article'
                );


            card.className =
                'update-card';


            if (
                Number(
                    notification.is_read
                ) === 0
            ) {

                card.classList.add(
                    'update-card--unread'
                );
            }


            card.innerHTML = `

                <h3 class="update-title">

                    ${escapeHtml(
                notification.title ||
                'Notification'
            )}

                </h3>


                <p class="update-text">

                    ${escapeHtml(
                notification.message ||
                ''
            )}

                </p>


                <div class="result-footer">

                    ${escapeHtml(
                formatUpdateDate(
                    notification.created_at
                )
            )}

                    ${Number(
                notification.is_read
            ) === 0
                    ? ' • New'
                    : ''
                }

                </div>

            `;


            container.appendChild(
                card
            );
        }
    );
}


// SEARCH UPDATES

function filterUpdates() {

    const input =
        document.getElementById(
            'updates-search'
        );


    const search =
        input
            ? input.value
                .trim()
                .toLowerCase()
            : '';


    if (!search) {

        displayApplicationUpdates(
            allApplicationUpdates
        );


        displayNotificationUpdates(
            allNotificationUpdates
        );


        return;
    }


    const applications =
        allApplicationUpdates.filter(
            application => {

                const searchable = `

                    ${application
                        .scholarship_title ||
                    ''
                    }

                    ${application.provider ||
                    ''
                    }

                    ${application.status ||
                    ''
                    }

                    ${application.country ||
                    ''
                    }

                    ${application.notes ||
                    ''
                    }

                `.toLowerCase();


                return searchable.includes(
                    search
                );
            }
        );


    const notifications =
        allNotificationUpdates.filter(
            notification => {

                const searchable = `

                    ${notification.title ||
                    ''
                    }

                    ${notification.message ||
                    ''
                    }

                `.toLowerCase();


                return searchable.includes(
                    search
                );
            }
        );


    displayApplicationUpdates(
        applications
    );


    displayNotificationUpdates(
        notifications
    );
}


// UPDATE DATE

function formatUpdateDate(
    value
) {

    if (!value) {

        return 'Date not available';
    }


    const date =
        new Date(
            String(value)
                .replace(
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
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }
    );
}


// UPDATES MESSAGE

function showUpdatesMessage(
    message,
    type = 'success',
    temporary = false
) {

    const box =
        document.getElementById(
            'updates-message'
        );


    if (!box) {
        return;
    }


    box.textContent =
        message;


    box.style.display =
        'block';


    box.style.padding =
        '12px 16px';


    box.style.marginBottom =
        '20px';


    box.style.borderRadius =
        '8px';


    if (
        type === 'error'
    ) {

        box.style.background =
            '#fff1f0';

        box.style.color =
            '#b42318';

    } else {

        box.style.background =
            '#eef4ff';

        box.style.color =
            '#2f6fed';
    }


    if (temporary) {

        window.setTimeout(
            () => {

                box.style.display =
                    'none';

            },
            2500
        );
    }
}

// DASHBOARD QUICK SCHOLARSHIP SEARCH

function initQuickScholarshipSearch() {

    const searchButton =
        document.getElementById(
            'university-search-btn'
        );


    //Not dashboard.html

    if (!searchButton) {
        return;
    }


    searchButton.addEventListener(
        'click',
        performQuickScholarshipSearch
    );


    //Allow Enter from study field

    const studyInput =
        document.getElementById(
            'study'
        );


    if (studyInput) {

        studyInput.addEventListener(
            'keydown',
            event => {

                if (
                    event.key ===
                    'Enter'
                ) {

                    event.preventDefault();

                    performQuickScholarshipSearch();
                }
            }
        );
    }
}


//PERFORM QUICK SEARCH

async function performQuickScholarshipSearch() {

    const button =
        document.getElementById(
            'university-search-btn'
        );


    const resultsWrapper =
        document.getElementById(
            'quick-search-results-wrapper'
        );


    const resultsContainer =
        document.getElementById(
            'quick-search-results'
        );


    const countElement =
        document.getElementById(
            'quick-search-count'
        );


    if (
        !resultsWrapper ||
        !resultsContainer
    ) {

        return;
    }


    // READ FILTERS

    const study =
        document.getElementById(
            'study'
        )?.value.trim() || '';


    const country =
        document.getElementById(
            'country'
        )?.value || '';


    const funding =
        document.getElementById(
            'budget'
        )?.value || '';


    const studyLevel =
        document.getElementById(
            'study-level'
        )?.value || '';


    const accommodation =
        document.getElementById(
            'accommodation'
        )?.value || '';



    const params =
        new URLSearchParams();


    if (study) {

        params.set(
            'study',
            study
        );
    }


    if (country) {

        params.set(
            'country',
            country
        );
    }


    if (funding) {

        params.set(
            'funding',
            funding
        );
    }


    if (studyLevel) {

        params.set(
            'study_level',
            studyLevel
        );
    }


    if (accommodation) {

        params.set(
            'accommodation',
            accommodation
        );
    }


    resultsWrapper.style.display =
        'block';


    resultsContainer.innerHTML = `

        <div class="quick-search-loading">

            <span class="quick-search-spinner">
            </span>

            <p>
                Searching available scholarships...
            </p>

        </div>
    `;


    if (countElement) {

        countElement.textContent =
            'Searching...';
    }


    if (button) {

        button.disabled =
            true;

        button.textContent =
            'Searching...';
    }


    hideQuickSearchMessage();


    try {

        // FETCH DATABASE

        const response =
            await fetch(

                '../backend/search_scholarships.php?' +
                params.toString(),

                {
                    method:
                        'GET',

                    credentials:
                        'include',

                    cache:
                        'no-store'
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (error) {

            console.error(
                'QUICK SEARCH PHP RESPONSE:',
                responseText
            );


            throw new Error(
                'The scholarship search returned an invalid response.'
            );
        }


        console.log(
            'QUICK SCHOLARSHIP SEARCH:',
            data
        );


        // LOGIN

        if (
            data.logged_in ===
            false
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }


        // ERROR

        if (
            !response.ok ||
            !data.success
        ) {

            throw new Error(
                data.message ||
                'Unable to search scholarships.'
            );
        }


        //DISPLAY

        const scholarships =
            Array.isArray(
                data.scholarships
            )
                ? data.scholarships
                : [];


        displayQuickScholarshipResults(
            scholarships
        );


    } catch (error) {

        console.error(
            'Quick scholarship search failed:',
            error
        );


        resultsContainer.innerHTML = `

            <div class="quick-search-empty">

                <strong>
                    Search unavailable
                </strong>

                <p>
                    ${escapeHtml(
            error.message
        )}
                </p>

            </div>
        `;


        if (countElement) {

            countElement.textContent =
                'Search failed';
        }


        showQuickSearchMessage(
            error.message ||
            'Unable to search scholarships.',
            'error'
        );


    } finally {

        if (button) {

            button.disabled =
                false;

            button.textContent =
                '🔍 Search Scholarships';
        }
    }
}


//DISPLAY QUICK SEARCH RESULTS

function displayQuickScholarshipResults(
    scholarships
) {

    const container =
        document.getElementById(
            'quick-search-results'
        );


    const countElement =
        document.getElementById(
            'quick-search-count'
        );


    if (!container) {
        return;
    }


    container.innerHTML =
        '';

    //count

    if (countElement) {

        countElement.textContent =
            `${scholarships.length} scholarship${scholarships.length === 1
                ? ''
                : 's'
            } found`;
    }


    //if empty

    if (
        scholarships.length ===
        0
    ) {

        container.innerHTML = `

            <div class="quick-search-empty">

                <div class="quick-search-empty-icon">
                    🔎
                </div>

                <h4>
                    No scholarships found
                </h4>

                <p>
                    Try changing your course,
                    country, funding or study
                    level.
                </p>

            </div>
        `;


        return;
    }


    //results

    scholarships.forEach(
        scholarship => {

            const card =
                document.createElement(
                    'article'
                );


            card.className =
                'quick-scholarship-card';


            const fundingLabel =
                formatQuickFunding(
                    scholarship
                        .funding_type
                );


            const deadline =
                formatQuickSearchDate(
                    scholarship.deadline
                );


            const benefits =
                Array.isArray(
                    scholarship.benefits
                )
                    ? scholarship.benefits
                    : [];


            const hasAccommodation =
                benefits.some(
                    benefit =>
                        benefit.benefit_type ===
                        'accommodation'
                );


            card.innerHTML = `

                <div class="quick-scholarship-top">

                    <div>

                        <span class="quick-scholarship-country">

                            ${escapeHtml(
                scholarship.country ||
                'International'
            )}

                        </span>


                        <h4>

                            ${escapeHtml(
                scholarship.title ||
                'Untitled Scholarship'
            )}

                        </h4>


                        <p class="quick-scholarship-provider">

                            ${escapeHtml(
                scholarship.provider ||
                'Provider not specified'
            )}

                        </p>

                    </div>


                    <span class="quick-funding-badge">

                        ${escapeHtml(
                fundingLabel
            )}

                    </span>

                </div>


                <p class="quick-scholarship-description">

                    ${escapeHtml(
                truncateQuickSearchText(
                    scholarship.description ||
                    'No description provided.',
                    180
                )
            )}

                </p>


                <div class="quick-scholarship-info">

                    <span>
                        🎓
                        ${escapeHtml(
                scholarship.education_level ||
                'Any level'
            )}
                    </span>


                    <span>
                        📚
                        ${escapeHtml(
                scholarship.eligible_courses ||
                'Multiple fields'
            )}
                    </span>


                    <span>
                        🏫
                        ${escapeHtml(
                scholarship.university ||
                'University not specified'
            )}
                    </span>


                    <span>
                        🏠
                        ${hasAccommodation
                    ? 'Accommodation included'
                    : 'Accommodation not listed'
                }
                    </span>

                </div>


                <div class="quick-scholarship-footer">

                    <div class="quick-deadline">

                        <small>
                            Application Deadline
                        </small>

                        <strong>
                            ${escapeHtml(
                    deadline
                )}
                        </strong>

                    </div>


                    <div class="quick-scholarship-actions">

                        <button
                            type="button"
                            class="quick-save-btn"
                        >
                            ♡ Save
                        </button>


                        ${scholarship.application_link
                    ? `

                                <a
                                    href="${escapeHtml(
                        scholarship.application_link
                    )}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="quick-apply-btn"
                                >
                                    Apply
                                </a>

                                `
                    : ''
                }

                    </div>

                </div>

            `;


            //save

            const saveButton =
                card.querySelector(
                    '.quick-save-btn'
                );


            if (saveButton) {

                saveButton.addEventListener(
                    'click',
                    async () => {

                        await saveScholarship(
                            scholarship.scholarship_id,
                            saveButton
                        );
                    }
                );
            }


            container.appendChild(
                card
            );
        }
    );
}


//quick search helpers

function formatQuickFunding(
    funding
) {

    const values = {

        fully_funded:
            'Fully Funded',

        partially_funded:
            'Partially Funded',

        tuition_only:
            'Tuition Only'

    };


    return (
        values[funding] ||
        funding ||
        'Funding not specified'
    );
}


function formatQuickSearchDate(
    value
) {

    if (!value) {

        return 'No deadline listed';
    }


    const date =
        new Date(
            value + 'T00:00:00'
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return value;
    }


    return date.toLocaleDateString(
        undefined,
        {
            year:
                'numeric',

            month:
                'short',

            day:
                'numeric'
        }
    );
}


function truncateQuickSearchText(
    text,
    length
) {

    const value =
        String(
            text || ''
        );


    if (
        value.length <=
        length
    ) {

        return value;
    }


    return (
        value.substring(
            0,
            length
        ).trim() +
        '...'
    );
}


function showQuickSearchMessage(
    message,
    type = 'success'
) {

    const box =
        document.getElementById(
            'quick-search-message'
        );


    if (!box) {
        return;
    }


    box.textContent =
        message;


    box.className =
        'quick-search-message';


    box.classList.add(
        type === 'error'
            ? 'quick-search-message--error'
            : 'quick-search-message--success'
    );


    box.style.display =
        'block';
}


function hideQuickSearchMessage() {

    const box =
        document.getElementById(
            'quick-search-message'
        );


    if (box) {

        box.style.display =
            'none';
    }
}


function setupCollapsibleSidebar() {

    const sidebar =
        document.getElementById('sidebar');

    const toggleButton =
        document.getElementById('sidebar-toggle');

    if (!sidebar || !toggleButton) {
        return;
    }


    // Restore previous sidebar state
    const sidebarCollapsed =
        localStorage.getItem('sidebarCollapsed');

    if (sidebarCollapsed === 'true') {
        sidebar.classList.add('collapsed');
        toggleButton.textContent = '☰';
    }


    toggleButton.addEventListener('click', () => {

        sidebar.classList.toggle('collapsed');

        const collapsed =
            sidebar.classList.contains('collapsed');

        localStorage.setItem(
            'sidebarCollapsed',
            collapsed
        );

        toggleButton.setAttribute(
            'aria-label',
            collapsed
                ? 'Expand sidebar'
                : 'Collapse sidebar'
        );

    });
}

