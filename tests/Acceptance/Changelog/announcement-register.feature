Feature:
    Ability to register announcements as admin

    Scenario: As admin I can access the register form
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As admin I register an announcement
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/register"
        When I submit a form with "category:feature|date:2026-09-22|title:Informes nuevos|summary:Resumen" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Novedad registrada correctamente: Informes nuevos"
        Then I see a text "Borrador"

    Scenario: As admin I register an announcement browsing in english
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/en/admin/announcements/register"
        When I submit a form with "category:feature|date:2026-09-22|title:New reports|summary:Summary" and button "Register"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "New reports"

    Scenario: An invalid YouTube link keeps the form with an error
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/register"
        When I submit a form with "category:feature|date:2026-09-22|title:Informes|summary:Resumen|youtubeUrl:https//vimeo.com/123" and button "Registrar"
        Then the response status should be 200
        Then I see a form
        Then I see a text "No es un enlace válido de YouTube"

    Scenario: As admin I register an announcement with a manual from the library
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given document "manual.pdf" with id "22222222-2222-2222-2222-222222222222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/register"
        When I submit a form with "category:feature|date:2026-09-22|title:Con manual|summary:Resumen|manualId:22222222-2222-2222-2222-222222222222" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Novedad registrada correctamente: Con manual"

    Scenario: A manual that is not a PDF is rejected
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given media "logo.png" with id "33333333-3333-3333-3333-333333333333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/register"
        When I submit a form with "category:feature|date:2026-09-22|title:Con manual|summary:Resumen|manualId:33333333-3333-3333-3333-333333333333" and button "Registrar"
        Then the response status should be 200
        Then I see a form
        Then I see a text "El manual debe ser un PDF"

    Scenario: The title is required
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/register"
        When I submit a form with "category:feature|date:2026-09-22|title:|summary:Resumen" and button "Registrar"
        Then the response status should be 200
        Then I see a form
        Then I do not see a text "Novedad registrada correctamente"

    Scenario: As user I cannot register announcements
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/announcements/register"
        Then the response status should be 403
