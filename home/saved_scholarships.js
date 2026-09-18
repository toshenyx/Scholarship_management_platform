document.addEventListener(
    'DOMContentLoaded',
    () => {
        loadSavedScholarships();
        loadSavedPageUser();
        setupSavedSearch();
    }
);


let savedScholarships = [];


/* =========================================================
   GET SAVED SCHOLARSHIPS FROM PHP / MYSQL
========================================================= */

async function loadSavedScholarships() {

    const container =
        document.getElementById(
            'saved-scholarships-results'
        );

    const count =
        document.getElementById(
            'saved-results-count'
        );


    if (!container) {
        return;
    }


    container.innerHTML =
        '<p>Loading saved scholarships from database...</p>';


    try {

        const response =
            await fetch(
                '../backend/get_saved_scholarships.php',
                {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        const text =
            await response.text();


        console.log(
            'get_saved_scholarships.php response:',
            text
        );


        let data;


        try {

            data = JSON.parse(text);

        } catch (error) {

            throw new Error(
                'PHP returned invalid JSON: ' +
                text.substring(0, 200)
            );
        }


        if (
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }


        if (
            !response.ok ||
            data.success !== true
        ) {

            throw new Error(
                data.message ||
                'Backend could not load saved scholarships.'
            );
        }


        /*
         * PHP returns:
         *
         * {
         *     success: true,
         *     saved_scholarships: [...]
         * }
         */

        savedScholarships =
            Array.isArray(
                data.saved_scholarships
            )
                ? data.saved_scholarships
                : [];


        console.log(
            'DATABASE SAVED SCHOLARSHIPS:',
            savedScholarships
        );


        renderSavedScholarships(
            savedScholarships
        );


    } catch (error) {

        console.error(
            'Saved scholarships error:',
            error
        );


        if (count) {

            count.textContent =
                'Database connection failed';
        }


        container.innerHTML = `

            <div class="update-card">

                <h3>
                    Could not load saved scholarships
                </h3>

                <p>
                    ${escapeSavedHtml(
                        error.message
                    )}
                </p>

                <button
                    type="button"
                    id="retry-saved"
                    class="update-btn"
                >
                    Try Again
                </button>

            </div>

        `;


        document
            .getElementById(
                'retry-saved'
            )
            ?.addEventListener(
                'click',
                loadSavedScholarships
            );
    }
}


/* =========================================================
   DISPLAY DATABASE RESULTS
========================================================= */

function renderSavedScholarships(
    scholarships
) {

    const container =
        document.getElementById(
            'saved-scholarships-results'
        );

    const count =
        document.getElementById(
            'saved-results-count'
        );


    if (!container) {
        return;
    }


    container.innerHTML = '';


    if (count) {

        count.textContent =
            `${scholarships.length} saved scholarship${
                scholarships.length === 1
                    ? ''
                    : 's'
            }`;
    }


    /* =====================================================
       DATABASE RETURNED ZERO ROWS
    ===================================================== */

    if (
        scholarships.length === 0
    ) {

        container.innerHTML = `

            <div class="update-card">

                <h3>
                    No saved scholarships yet
                </h3>

                <p>
                    You currently have no scholarships
                    saved in your account.
                </p>

                <a
                    href="scholarships.html"
                    class="update-btn"
                >
                    Browse Scholarships
                </a>

            </div>

        `;

        return;
    }


    /* =====================================================
       CREATE A CARD FROM EACH DATABASE ROW
    ===================================================== */

    scholarships.forEach(
        scholarship => {

            const card =
                document.createElement(
                    'article'
                );


            card.className =
                'update-card saved-scholarship-card';


            const applicationLink =
                safeUrl(
                    scholarship.application_link
                );


            card.innerHTML = `

                <div class="saved-card-header">

                    <div>

                        <h3 class="update-title">

                            ${escapeSavedHtml(
                                scholarship.title ||
                                'Scholarship'
                            )}

                        </h3>

                        <p class="update-text">

                            ${escapeSavedHtml(
                                scholarship.provider ||
                                'Provider not specified'
                            )}

                        </p>

                    </div>

                </div>


                <p class="update-text">

                    ${escapeSavedHtml(
                        scholarship.description ||
                        'No description available.'
                    )}

                </p>


                <div class="saved-scholarship-details">

                    <p>
                        <strong>Country:</strong>
                        ${escapeSavedHtml(
                            scholarship.country ||
                            'Not specified'
                        )}
                    </p>


                    <p>
                        <strong>University:</strong>
                        ${escapeSavedHtml(
                            scholarship.university ||
                            'Not specified'
                        )}
                    </p>


                    <p>
                        <strong>Education level:</strong>
                        ${escapeSavedHtml(
                            scholarship.education_level ||
                            'Not specified'
                        )}
                    </p>


                    <p>
                        <strong>Eligible courses:</strong>
                        ${escapeSavedHtml(
                            scholarship.eligible_courses ||
                            'Not specified'
                        )}
                    </p>


                    <p>
                        <strong>Funding:</strong>
                        ${escapeSavedHtml(
                            formatFunding(
                                scholarship.funding_type
                            )
                        )}
                    </p>


                    <p>
                        <strong>Minimum GPA:</strong>
                        ${escapeSavedHtml(
                            scholarship.minimum_gpa ??
                            'Not specified'
                        )}
                    </p>


                    <p>
                        <strong>Deadline:</strong>
                        ${escapeSavedHtml(
                            formatDate(
                                scholarship.deadline
                            )
                        )}
                    </p>

                </div>


                <div class="update-actions">

                    <button
                        type="button"
                        class="update-btn remove-saved"
                        data-id="${Number(
                            scholarship.scholarship_id
                        )}"
                    >
                        Remove
                    </button>


                    ${
                        applicationLink
                            ? `

                            <a
                                href="${escapeSavedHtml(
                                    applicationLink
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


            const removeButton =
                card.querySelector(
                    '.remove-saved'
                );


            if (removeButton) {

                removeButton.addEventListener(
                    'click',
                    () => {

                        removeSavedScholarship(
                            scholarship.scholarship_id,
                            removeButton
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


/* =========================================================
   REMOVE SAVED SCHOLARSHIP
========================================================= */

async function removeSavedScholarship(
    scholarshipId,
    button
) {

    const oldText =
        button.textContent;


    button.disabled = true;

    button.textContent =
        'Removing...';


    try {

        const response =
            await fetch(
                '../backend/remove_saved_scholarship.php',
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


        const text =
            await response.text();


        console.log(
            'Remove response:',
            text
        );


        const data =
            JSON.parse(text);


        if (
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }


        if (
            !response.ok ||
            data.success !== true
        ) {

            throw new Error(
                data.message ||
                'Could not remove scholarship.'
            );
        }


        /*
         * Do NOT just hide the HTML card.
         *
         * Fetch MySQL again so the screen
         * reflects the database.
         */

        await loadSavedScholarships();


    } catch (error) {

        console.error(
            error
        );


        button.disabled = false;

        button.textContent =
            oldText;


        alert(
            error.message ||
            'Unable to remove scholarship.'
        );
    }
}


/* =========================================================
   SEARCH
========================================================= */

function setupSavedSearch() {

    const input =
        document.getElementById(
            'saved-search'
        );

    const button =
        document.getElementById(
            'saved-search-btn'
        );


    if (input) {

        input.addEventListener(
            'input',
            filterSavedScholarships
        );
    }


    if (button) {

        button.addEventListener(
            'click',
            filterSavedScholarships
        );
    }
}


function filterSavedScholarships() {

    const input =
        document.getElementById(
            'saved-search'
        );


    const term =
        input
            ? input.value
                .trim()
                .toLowerCase()
            : '';


    if (!term) {

        renderSavedScholarships(
            savedScholarships
        );

        return;
    }


    const filtered =
        savedScholarships.filter(
            scholarship => {

                const value = `

                    ${scholarship.title || ''}
                    ${scholarship.provider || ''}
                    ${scholarship.country || ''}
                    ${scholarship.university || ''}
                    ${scholarship.eligible_courses || ''}
                    ${scholarship.education_level || ''}

                `.toLowerCase();


                return value.includes(
                    term
                );
            }
        );


    renderSavedScholarships(
        filtered
    );
}


/* =========================================================
   LOAD USER INFORMATION
========================================================= */

async function loadSavedPageUser() {

    const username =
        document.getElementById(
            'page-username'
        );

    const role =
        document.getElementById(
            'page-role'
        );


    try {

        const response =
            await fetch(
                '../backend/get_dashboard.php',
                {
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        const data =
            await response.json();


        if (
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return;
        }


        if (username) {

            username.textContent =
                data.username ||
                'User';
        }


        if (role) {

            role.textContent =
                capitalize(
                    data.role ||
                    'student'
                );
        }


    } catch (error) {

        console.error(
            'User loading error:',
            error
        );


        if (username) {

            username.textContent =
                'User';
        }


        if (role) {

            role.textContent =
                'Student';
        }
    }
}


/* =========================================================
   HELPERS
========================================================= */

function formatFunding(value) {

    if (!value) {
        return 'Not specified';
    }


    return String(value)
        .replaceAll(
            '_',
            ' '
        )
        .replace(
            /\b\w/g,
            letter =>
                letter.toUpperCase()
        );
}


function formatDate(value) {

    if (!value) {
        return 'Not specified';
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
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }
    );
}


function safeUrl(value) {

    if (!value) {
        return '';
    }


    try {

        const url =
            new URL(
                value,
                window.location.href
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


function capitalize(value) {

    value =
        String(
            value || ''
        );


    return value
        ? value.charAt(0).toUpperCase() +
          value.slice(1)
        : '';
}


function escapeSavedHtml(value) {

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