Feature:
    I want the sidebar menus to group their screens

    Scenario: An admin sees the administration menu grouped
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text "Organización"
        Then I see a text "Auditoría"
        Then I see a text 'sidebar-divider lg'
        Then I see a text "Organización" before a text "Auditoría"
        Then I see a text '<span class="hide-menu">Organización</span>' before a text '<span class="hide-menu">Bioscore</span>'
        Then I see a text '<span class="hide-menu">Bioscore</span>' before a text '<span class="hide-menu">Auditoría</span>'
        Then I see a text "Auditoría" before a text 'href="/es/admin/events"'
        Then I see a text 'class="sidebar-link active" href="/es/admin/users"'
        Then I see a text 'mini-nav-item selected'
        Then I do not see a text 'href="/es/admin/emails"'

    Scenario: Loose links come before the groups of a root
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text '<span class="hide-menu">Administración</span>' before a text 'href="/es/admin/exchange-rates"'
        Then I see a text 'href="/es/admin/exchange-rates"' before a text '<span class="hide-menu">Organización</span>'
        Then I see 9 times the text 'sidebar-divider lg'

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

    Scenario: An admin sees the activity masters grouped in a single root
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materials"
        Then the response status should be 200
        Then I see a text "Maestros"
        Then I see a text '<span class="hide-menu">Maestros</span>' before a text '<span class="hide-menu">Materiales</span>'
        Then I see a text "Materiales" before a text "Productos vendidos"
        Then I see a text "Productos vendidos" before a text "Inversiones"
        Then I see a text "Inversiones" before a text "Instalaciones"
        Then I see a text "Instalaciones" before a text "Transporte"
        Then I see a text 'class="sidebar-link active" href="/es/admin/materials"'
        Then I see a text 'mini-nav-item selected'

    Scenario: The first masters group has no divider
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materials"
        Then the response status should be 200
        Then I see a text '<span class="hide-menu">Materiales</span>' before a text 'sidebar-divider lg'

    Scenario: A master screen selects its group inside the masters root
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/combustibles"
        Then the response status should be 200
        Then I see a text 'class="sidebar-link active" href="/es/admin/combustibles"'
        Then I see a text "Instalaciones" before a text 'href="/es/admin/combustibles"'
        Then I see a text 'href="/es/admin/combustibles"' before a text "Transporte"

    Scenario: The mitigation measures are a group inside the carbon footprint root
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measures"
        Then the response status should be 200
        Then I see a text 'class="sidebar-link active" href="/es/admin/mitigation-measures"'
        Then I see a text '<span class="hide-menu">Huella de carbono</span>' before a text 'href="/es/admin/emission-factor-groups"'
        Then I see a text 'href="/es/admin/emission-factor-groups"' before a text '<span class="hide-menu">Medidas de mitigación</span>'
        Then I see a text '<span class="hide-menu">Medidas de mitigación</span>' before a text 'href="/es/admin/mitigation-measures"'
        Then I see a text 'href="/es/admin/mitigation-measures"' before a text '<span class="hide-menu">Materialidad</span>'

    Scenario: An owner does not see the masters root
        Given account "Acme" with id "a1" exists
        Given user "acme-owner@dediez.software" with role "ROLE_OWNER" of account "a1" exists
        When I am logged as "acme-owner@dediez.software"
        When I go to "/es/users"
        Then the response status should be 200
        Then I do not see a text "Maestros"
