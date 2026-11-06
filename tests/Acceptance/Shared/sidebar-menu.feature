Feature:
    I want the sidebar menus to group their screens

    Scenario: An admin sees the security menu grouped
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text "Organización"
        Then I see a text "Auditoría"
        Then I see a text 'sidebar-divider lg'
        Then I see a text "Organización" before a text "Auditoría"
        Then I see a text "Auditoría" before a text 'href="/es/admin/events"'
        Then I see a text 'class="sidebar-link active" href="/es/admin/users"'
        Then I see a text 'mini-nav-item selected'
        Then I do not see a text 'href="/es/admin/emails"'

    Scenario: The first group has no divider
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text '<span class="hide-menu">Seguridad</span>' before a text '<span class="hide-menu">Organización</span>'
        Then I see a text "Organización" before a text 'sidebar-divider lg'

    Scenario: A super admin sees the sent emails inside the audit group
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text "Auditoría" before a text 'href="/es/admin/emails"'
        Then I see a text 'href="/es/admin/emails"' before a text 'href="/es/admin/events"'

    Scenario: An owner sees no groups
        Given account "Acme" with id "a1" exists
        Given user "acme-owner@dediez.software" with role "ROLE_OWNER" of account "a1" exists
        When I am logged as "acme-owner@dediez.software"
        When I go to "/es/users"
        Then the response status should be 200
        Then I do not see a text 'sidebar-divider lg'
        Then I do not see a text "Organización"
