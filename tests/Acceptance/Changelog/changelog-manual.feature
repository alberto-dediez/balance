Feature:
    Ability to download and view changelog manuals in web

    Scenario: Downloading the user manual returns the PDF
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "con-manual" titled "Con manual" in category "feature" exists
        Given changelog entry "con-manual" has a user manual
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog/con-manual/manual"
        Then the response status should be 200

    Scenario: Requesting the manual of an entry without one returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "sin-manual" titled "Sin manual" in category "feature" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog/sin-manual/manual"
        Then the response status should be 404

    Scenario: The technical manual is shown as a rendered page to a super admin
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given changelog entry "con-tecnico" titled "Con técnico" in category "feature" exists
        Given changelog entry "con-tecnico" has a technical manual
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/admin/changelog/con-tecnico/manual-tecnico"
        Then the response status should be 200
        Then I see a text "Manual técnico de prueba"

    Scenario: As admin (not super admin) I cannot view the technical manual
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "con-tecnico" titled "Con técnico" in category "feature" exists
        Given changelog entry "con-tecnico" has a technical manual
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog/con-tecnico/manual-tecnico"
        Then the response status should be 403

    Scenario: As user I cannot download the user manual
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given changelog entry "con-manual" titled "Con manual" in category "feature" exists
        Given changelog entry "con-manual" has a user manual
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/changelog/con-manual/manual"
        Then the response status should be 403

    Scenario: As user I cannot view the technical manual
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given changelog entry "con-tecnico" titled "Con técnico" in category "feature" exists
        Given changelog entry "con-tecnico" has a technical manual
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/changelog/con-tecnico/manual-tecnico"
        Then the response status should be 403
