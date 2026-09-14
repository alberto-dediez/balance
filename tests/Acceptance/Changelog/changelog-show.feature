Feature:
    Ability to view a changelog entry in web

    Scenario: As admin I open an entry and see its rendered content
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "mi-entrada" titled "Mi entrada" in category "feature" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog/mi-entrada"
        Then the response status should be 200
        Then I see a text "Contenido de prueba"

    Scenario: An unknown slug returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog/no-existe"
        Then the response status should be 404

    Scenario: An unpublished entry returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "borrador" is not published
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog/borrador"
        Then the response status should be 404

    Scenario: An entry without manuals does not show the manual links
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "sin-manuales" titled "Sin manuales" in category "feature" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog/sin-manuales"
        Then the response status should be 200
        Then I do not see a text "Ver manual"

    Scenario: A super admin sees both manual links
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given changelog entry "con-manuales" titled "Con manuales" in category "feature" exists
        Given changelog entry "con-manuales" has a user manual
        Given changelog entry "con-manuales" has a technical manual
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/admin/changelog/con-manuales"
        Then the response status should be 200
        Then I see a link with name "Ver manual"
        Then I see a link with name "Manual técnico"

    Scenario: An admin (not super admin) sees the user manual link but not the technical one
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "con-manuales" titled "Con manuales" in category "feature" exists
        Given changelog entry "con-manuales" has a user manual
        Given changelog entry "con-manuales" has a technical manual
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog/con-manuales"
        Then the response status should be 200
        Then I see a link with name "Ver manual"
        Then I do not see a text "Manual técnico"

    Scenario: As owner I cannot access an entry detail
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        Given changelog entry "mi-entrada" titled "Mi entrada" in category "feature" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/changelog/mi-entrada"
        Then the response status should be 403
