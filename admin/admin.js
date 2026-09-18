let adminScholarships = [];
let pendingScholarships = [];
let adminUsers = [];


document.addEventListener(
    'DOMContentLoaded',
    async () => {

        const authorized =
            await checkAdminAccess();

        if (!authorized) {
            return;
        }


        const page =
            document.body.dataset.page;


        switch (page) {

            case 'dashboard':

                loadAdminDashboard();

                break;


            case 'scholarships':

                setupScholarshipPage();

                break;


            case 'pending':

                setupPendingPage();

                break;


            case 'users':

                setupUsersPage();

                break;


            case 'reports':

                loadAdminReport();

                break;


            case 'settings':

                setupAdminSettings();

                break;
        }

    }
);



/* =========================================================
   SECURITY
========================================================= */

async function checkAdminAccess() {

    try {

        const response =
            await fetch(
                '../backend/admin_check.php',
                {
                    method: 'GET',

                    credentials: 'include',

                    cache: 'no-store'
                }
            );


        /*
        |--------------------------------------------------------------------------
        | READ AS TEXT FIRST
        |--------------------------------------------------------------------------
        |
        | Don't immediately call response.json().
        | This lets us see PHP errors / empty responses.
        |
        */

        const responseText =
            await response.text();


        console.log(
            'admin_check.php response:',
            responseText
        );


        /*
        |--------------------------------------------------------------------------
        | EMPTY RESPONSE
        |--------------------------------------------------------------------------
        */

        if (!responseText.trim()) {

            console.error(
                'admin_check.php returned an empty response.'
            );

            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | CONVERT TO JSON
        |--------------------------------------------------------------------------
        */

        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (jsonError) {

            console.error(
                'admin_check.php returned invalid JSON:',
                responseText
            );


            console.error(
                jsonError
            );


            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | NOT LOGGED IN
        |--------------------------------------------------------------------------
        */

        if (
            response.status === 401 ||
            data.logged_in === false
        ) {

            window.location.href =
                '../signin/signin.html';

            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | LOGGED IN BUT NOT ADMIN
        |--------------------------------------------------------------------------
        */

        if (
            response.status === 403 ||
            data.authorized === false
        ) {

            alert(
                'Administrator access required.'
            );


            window.location.href =
                '../home/dashboard.html';

            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | ADMIN AUTHORIZED
        |--------------------------------------------------------------------------
        */

        if (
            response.ok &&
            data.success === true &&
            data.authorized === true
        ) {

            console.log(
                'Admin authorized:',
                data.admin
            );


            /*
             * If this page contains #admin-name,
             * display the logged-in admin username.
             */

            const adminName =
                document.getElementById(
                    'admin-name'
                );


            if (
                adminName &&
                data.admin
            ) {

                adminName.textContent =
                    data.admin.username
                    || 'Admin';
            }


            return true;
        }


        console.error(
            'Admin authorization failed:',
            data
        );


        return false;


    } catch (error) {

        console.error(
            'Admin authorization error:',
            error
        );


        return false;
    }

}



/* =========================================================
   DASHBOARD
========================================================= */

async function loadAdminDashboard() {

    try {

        const response =
            await fetch(
                '../backend/admin_get_dashboard.php',
                {
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        const data =
            await response.json();


        if (!data.success) {
            return;
        }


        setText(
            'admin-name',
            data.admin.username
        );


        setText(
            'stat-students',
            data.stats.students
        );


        setText(
            'stat-scholarships',
            data.stats.scholarships
        );


        setText(
            'stat-pending',
            data.stats.pending
        );


        setText(
            'stat-approved',
            data.stats.approved
        );


        displayRecentScholarships(
            data.recent_scholarships
        );


    } catch (error) {

        console.error(
            'Dashboard error:',
            error
        );

    }

}



function displayRecentScholarships(
    scholarships
) {

    const tbody =
        document.getElementById(
            'recent-table'
        );


    if (!tbody) {
        return;
    }


    if (!scholarships.length) {

        tbody.innerHTML = `
            <tr>
                <td colspan="5">
                    No scholarships found.
                </td>
            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        scholarships
            .map(
                scholarship => `
                    <tr>

                        <td>
                            ${escapeHtml(
                                scholarship.title
                            )}
                        </td>

                        <td>
                            ${escapeHtml(
                                scholarship.provider
                            )}
                        </td>

                        <td>
                            ${escapeHtml(
                                scholarship.posted_by_username
                                || 'Unknown'
                            )}
                        </td>

                        <td>
                            ${escapeHtml(
                                scholarship.country
                                || '-'
                            )}
                        </td>

                        <td>

                            ${statusBadge(
                                scholarship.verification_status
                            )}

                        </td>

                    </tr>
                `
            )
            .join('');

}



/* =========================================================
   ALL SCHOLARSHIPS
========================================================= */

function setupScholarshipPage() {

    const search =
        document.getElementById(
            'scholarship-search'
        );


    const filter =
        document.getElementById(
            'status-filter'
        );


    search?.addEventListener(
        'input',
        filterScholarships
    );


    filter?.addEventListener(
        'change',
        filterScholarships
    );


    loadAdminScholarships();

}



async function loadAdminScholarships() {

    try {

        const response =
            await fetch(
                '../backend/admin_get_scholarships.php?status=all',
                {
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        const data =
            await response.json();


        if (!data.success) {

            showMessage(
                data.message ||
                'Could not load scholarships.',
                'error'
            );

            return;
        }


        adminScholarships =
            data.scholarships || [];


        displayScholarships(
            adminScholarships
        );


    } catch (error) {

        console.error(error);

        showMessage(
            'Could not load scholarships.',
            'error'
        );
    }

}



function filterScholarships() {

    const search =
        document.getElementById(
            'scholarship-search'
        )?.value
        .trim()
        .toLowerCase() || '';


    const status =
        document.getElementById(
            'status-filter'
        )?.value || 'all';


    const filtered =
        adminScholarships.filter(
            scholarship => {

                const matchesSearch =
                    (
                        scholarship.title +
                        ' ' +
                        scholarship.provider +
                        ' ' +
                        scholarship.country
                    )
                    .toLowerCase()
                    .includes(search);


                const matchesStatus =
                    status === 'all' ||
                    scholarship.verification_status
                    === status;


                return (
                    matchesSearch &&
                    matchesStatus
                );
            }
        );


    displayScholarships(filtered);

}



function displayScholarships(
    scholarships
) {

    const tbody =
        document.getElementById(
            'scholarships-table'
        );


    if (!tbody) {
        return;
    }


    if (!scholarships.length) {

        tbody.innerHTML = `
            <tr>
                <td colspan="7">
                    No scholarships found.
                </td>
            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        scholarships
        .map(
            scholarship => {

                const id =
                    scholarship.scholarship_id;


                return `
                    <tr>

                        <td>
                            <strong>
                                ${escapeHtml(
                                    scholarship.title
                                )}
                            </strong>
                        </td>


                        <td>
                            ${escapeHtml(
                                scholarship.provider
                            )}
                        </td>


                        <td>
                            ${escapeHtml(
                                scholarship.country
                                || '-'
                            )}
                        </td>


                        <td>
                            ${formatDate(
                                scholarship.deadline
                            )}
                        </td>


                        <td>
                            ${escapeHtml(
                                scholarship.posted_by_username
                                || 'Unknown'
                            )}
                        </td>


                        <td>
                            ${statusBadge(
                                scholarship.verification_status
                            )}
                        </td>


                        <td>

                            <div class="action-buttons">

                                ${
                                    scholarship.verification_status
                                    !== 'approved'
                                    ? `
                                        <button
                                            class="btn btn-success"
                                            onclick="
                                                reviewScholarship(
                                                    ${id},
                                                    'approve'
                                                )
                                            "
                                        >
                                            Approve
                                        </button>
                                    `
                                    : ''
                                }


                                ${
                                    scholarship.verification_status
                                    !== 'rejected'
                                    ? `
                                        <button
                                            class="btn btn-warning"
                                            onclick="
                                                reviewScholarship(
                                                    ${id},
                                                    'reject'
                                                )
                                            "
                                        >
                                            Reject
                                        </button>
                                    `
                                    : ''
                                }


                                <button
                                    class="btn btn-danger"
                                    onclick="
                                        deleteScholarship(
                                            ${id}
                                        )
                                    "
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>
                `;
            }
        )
        .join('');

}



/* =========================================================
   PENDING
========================================================= */

function setupPendingPage() {

    const search =
        document.getElementById(
            'pending-search'
        );


    search?.addEventListener(
        'input',
        filterPendingScholarships
    );


    loadPendingScholarships();

}



async function loadPendingScholarships() {

    try {

        const response =
            await fetch(
                '../backend/admin_get_scholarships.php?status=pending',
                {
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        const data =
            await response.json();


        pendingScholarships =
            data.scholarships || [];


        displayPendingScholarships(
            pendingScholarships
        );


    } catch (error) {

        console.error(error);

    }

}



function filterPendingScholarships() {

    const search =
        document.getElementById(
            'pending-search'
        )?.value
        .trim()
        .toLowerCase() || '';


    const filtered =
        pendingScholarships.filter(
            scholarship =>
                (
                    scholarship.title +
                    ' ' +
                    scholarship.provider +
                    ' ' +
                    scholarship.country
                )
                .toLowerCase()
                .includes(search)
        );


    displayPendingScholarships(
        filtered
    );

}



function displayPendingScholarships(
    scholarships
) {

    const tbody =
        document.getElementById(
            'pending-table'
        );


    if (!tbody) {
        return;
    }


    if (!scholarships.length) {

        tbody.innerHTML = `
            <tr>

                <td colspan="6">
                    No scholarships are waiting
                    for review.
                </td>

            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        scholarships
        .map(
            scholarship => `

                <tr>

                    <td>
                        <strong>
                            ${escapeHtml(
                                scholarship.title
                            )}
                        </strong>
                    </td>


                    <td>
                        ${escapeHtml(
                            scholarship.provider
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            scholarship.posted_by_username
                            || 'Unknown'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            scholarship.country
                            || '-'
                        )}
                    </td>


                    <td>
                        ${formatDate(
                            scholarship.deadline
                        )}
                    </td>


                    <td>

                        <div class="action-buttons">

                            <button
                                class="btn btn-success"
                                onclick="
                                    reviewScholarship(
                                        ${scholarship.scholarship_id},
                                        'approve'
                                    )
                                "
                            >
                                Approve
                            </button>


                            <button
                                class="btn btn-danger"
                                onclick="
                                    reviewScholarship(
                                        ${scholarship.scholarship_id},
                                        'reject'
                                    )
                                "
                            >
                                Reject
                            </button>

                        </div>

                    </td>

                </tr>
            `
        )
        .join('');

}



/* =========================================================
   APPROVE / REJECT
========================================================= */

async function reviewScholarship(
    scholarshipId,
    action
) {

    const word =
        action === 'approve'
            ? 'approve'
            : 'reject';


    if (
        !confirm(
            `Are you sure you want to ${word} this scholarship?`
        )
    ) {
        return;
    }


    try {

        const response =
            await fetch(
                '../backend/admin_review_scholarship.php',
                {
                    method: 'POST',

                    credentials: 'include',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    body: JSON.stringify({
                        scholarship_id:
                            scholarshipId,

                        action:
                            action
                    })
                }
            );


        const data =
            await response.json();


        showMessage(
            data.message,
            data.success
                ? 'success'
                : 'error'
        );


        if (!data.success) {
            return;
        }


        if (
            document.body.dataset.page
            === 'pending'
        ) {

            loadPendingScholarships();

        } else {

            loadAdminScholarships();

        }


    } catch (error) {

        console.error(error);

        showMessage(
            'Could not update scholarship.',
            'error'
        );

    }

}



/* =========================================================
   DELETE
========================================================= */

async function deleteScholarship(
    scholarshipId
) {

    if (
        !confirm(
            'Delete this scholarship permanently?'
        )
    ) {
        return;
    }


    try {

        const response =
            await fetch(
                '../backend/admin_delete_scholarship.php',
                {
                    method: 'POST',

                    credentials: 'include',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    body: JSON.stringify({
                        scholarship_id:
                            scholarshipId
                    })
                }
            );


        const data =
            await response.json();


        showMessage(
            data.message,
            data.success
                ? 'success'
                : 'error'
        );


        if (data.success) {

            loadAdminScholarships();

        }


    } catch (error) {

        console.error(error);

        showMessage(
            'Could not delete scholarship.',
            'error'
        );

    }

}



/* =========================================================
   USERS
========================================================= */

function setupUsersPage() {

    document
        .getElementById(
            'user-search'
        )
        ?.addEventListener(
            'input',
            filterUsers
        );


    document
        .getElementById(
            'role-filter'
        )
        ?.addEventListener(
            'change',
            filterUsers
        );


    loadAdminUsers();

}



async function loadAdminUsers() {

    try {

        const response =
            await fetch(
                '../backend/admin_get_users.php',
                {
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        const data =
            await response.json();


        adminUsers =
            data.users || [];


        displayUsers(
            adminUsers
        );


    } catch (error) {

        console.error(error);

    }

}



function filterUsers() {

    const search =
        document.getElementById(
            'user-search'
        )?.value
        .trim()
        .toLowerCase() || '';


    const role =
        document.getElementById(
            'role-filter'
        )?.value || 'all';


    const filtered =
        adminUsers.filter(
            user => {

                const text =
                    (
                        (user.full_name || '') +
                        ' ' +
                        user.username +
                        ' ' +
                        user.email
                    )
                    .toLowerCase();


                const roleMatch =
                    role === 'all' ||
                    user.role === role;


                return (
                    text.includes(search) &&
                    roleMatch
                );
            }
        );


    displayUsers(filtered);

}



function displayUsers(users) {

    const tbody =
        document.getElementById(
            'users-table'
        );


    if (!tbody) {
        return;
    }


    if (!users.length) {

        tbody.innerHTML = `
            <tr>

                <td colspan="7">
                    No users found.
                </td>

            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        users
        .map(
            user => `

                <tr>

                    <td>

                        <strong>
                            ${escapeHtml(
                                user.full_name
                                || user.username
                            )}
                        </strong>

                        <br>

                        <small>
                            @${escapeHtml(
                                user.username
                            )}
                        </small>

                    </td>


                    <td>
                        ${escapeHtml(
                            user.email
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            user.role
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            user.nationality
                            || '-'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            user.education_level
                            || '-'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            user.course
                            || '-'
                        )}
                    </td>


                    <td>
                        ${formatDate(
                            user.created_at
                        )}
                    </td>

                </tr>
            `
        )
        .join('');

}



/* =========================================================
   REPORT
========================================================= */

async function loadAdminReport() {

    const container =
        document.getElementById(
            'report-content'
        );


    if (!container) {
        return;
    }


    try {

        const response =
            await fetch(
                '../backend/admin_get_reports.php',
                {
                    credentials: 'include',
                    cache: 'no-store'
                }
            );


        const data =
            await response.json();


        if (!data.success) {
            return;
        }


        const report =
            data.report;


        const countries =
            report.countries
            .map(
                country => `
                    <div class="report-row">

                        <span>
                            ${escapeHtml(
                                country.country
                            )}
                        </span>

                        <strong>
                            ${country.total}
                        </strong>

                    </div>
                `
            )
            .join('');


        container.innerHTML = `

            <h2 class="card-title">
                Scholarly Platform Report
            </h2>


            <p>
                Generated:
                ${escapeHtml(
                    data.generated_at
                )}
            </p>


            <br>


            <div class="report-section">

                <h3>
                    Users
                </h3>

                ${reportRow(
                    'Students',
                    report.users.students
                )}

                ${reportRow(
                    'Administrators',
                    report.users.admins
                )}

            </div>



            <div class="report-section">

                <h3>
                    Scholarships
                </h3>

                ${reportRow(
                    'Total Scholarships',
                    report.scholarships.total
                )}

                ${reportRow(
                    'Approved',
                    report.scholarships.approved
                )}

                ${reportRow(
                    'Pending',
                    report.scholarships.pending
                )}

                ${reportRow(
                    'Rejected',
                    report.scholarships.rejected
                )}

            </div>



            <div class="report-section">

                <h3>
                    Funding Types
                </h3>

                ${reportRow(
                    'Fully Funded',
                    report.funding.fully_funded
                )}

                ${reportRow(
                    'Partially Funded',
                    report.funding.partially_funded
                )}

                ${reportRow(
                    'Tuition Only',
                    report.funding.tuition_only
                )}

            </div>



            <div class="report-section">

                <h3>
                    Applications
                </h3>

                ${reportRow(
                    'Total Applications',
                    report.applications.total
                )}

                ${reportRow(
                    'Under Review',
                    report.applications.under_review
                )}

                ${reportRow(
                    'Accepted',
                    report.applications.accepted
                )}

                ${reportRow(
                    'Rejected',
                    report.applications.rejected
                )}

            </div>



            <div class="report-section">

                <h3>
                    Scholarships by Country
                </h3>

                ${
                    countries ||
                    '<p>No country data available.</p>'
                }

            </div>

        `;


    } catch (error) {

        console.error(error);

        container.innerHTML =
            '<p>Could not load report.</p>';

    }

}



function reportRow(
    label,
    value
) {

    return `
        <div class="report-row">

            <span>
                ${escapeHtml(label)}
            </span>

            <strong>
                ${value}
            </strong>

        </div>
    `;

}



/* =========================================================
   SETTINGS
========================================================= */

function setupAdminSettings() {

    const form =
        document.getElementById(
            'admin-password-form'
        );


    if (!form) {
        return;
    }


    form.addEventListener(
        'submit',
        async event => {

            event.preventDefault();


            const current =
                document.getElementById(
                    'current-password'
                ).value;


            const newPassword =
                document.getElementById(
                    'new-password'
                ).value;


            const confirmPassword =
                document.getElementById(
                    'confirm-password'
                ).value;


            if (
                newPassword !==
                confirmPassword
            ) {

                showMessage(
                    'New passwords do not match.',
                    'error'
                );

                return;
            }


            if (
                newPassword.length < 8
            ) {

                showMessage(
                    'Password must contain at least 8 characters.',
                    'error'
                );

                return;
            }


            const formData =
                new FormData();


            formData.append(
                'current_password',
                current
            );


            formData.append(
                'new_password',
                newPassword
            );


            formData.append(
                'confirm_password',
                confirmPassword
            );


            try {

                const response =
                    await fetch(
                        '../backend/change_password.php',
                        {
                            method: 'POST',

                            credentials:
                                'include',

                            body:
                                formData
                        }
                    );


                const data =
                    await response.json();


                showMessage(
                    data.message,
                    data.success
                        ? 'success'
                        : 'error'
                );


                if (data.success) {

                    form.reset();

                }


            } catch (error) {

                console.error(error);

                showMessage(
                    'Could not change password.',
                    'error'
                );

            }

        }
    );

}



/* =========================================================
   SHARED HELPERS
========================================================= */

function showMessage(
    message,
    type
) {

    const box =
        document.getElementById(
            'admin-message'
        );


    if (!box) {
        return;
    }


    box.textContent =
        message || '';


    box.className =
        `admin-message ${type}`;


    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });

}



function statusBadge(status) {

    const safeStatus =
        escapeHtml(
            status || 'unknown'
        );


    return `
        <span
            class="
                status
                status-${safeStatus}
            "
        >
            ${safeStatus}
        </span>
    `;

}



function formatDate(date) {

    if (!date) {
        return '-';
    }


    const parsed =
        new Date(
            `${date}`.replace(
                ' ',
                'T'
            )
        );


    if (
        Number.isNaN(
            parsed.getTime()
        )
    ) {
        return escapeHtml(date);
    }


    return parsed.toLocaleDateString(
        undefined,
        {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }
    );

}



function setText(
    id,
    value
) {

    const element =
        document.getElementById(id);


    if (element) {

        element.textContent =
            value ?? '';

    }

}



function escapeHtml(value) {

    return String(
        value ?? ''
    )
    .replaceAll(
        '&',
        '&amp;'
    )
    .replaceAll(
        '<',
        '&lt;'
    )
    .replaceAll(
        '>',
        '&gt;'
    )
    .replaceAll(
        '"',
        '&quot;'
    )
    .replaceAll(
        "'",
        '&#039;'
    );

}