Feature:
    I want to make sure that the software allows to edit a materiality sector

    Scenario: As an admin, I can edit the name of a materiality sector
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors/edit/111"
        When I submit a form with "name:Alojamientos" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Sector de materialidad actualizado con éxito"
        Then I see a text "Alojamientos"
        Then materiality sector "111" should have name "Alojamientos" in locale "es"

    Scenario: As an admin, editing in english creates a translation and keeps the spanish name
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/en/admin/materiality-sectors/edit/111"
        When I submit a form with "name:Hotels" and button "Save"
        When I follow redirect
        Then the response status should be 200
        Then materiality sector "111" should have name "Hotels" in locale "en"
        Then materiality sector "111" should have name "Hoteles" in locale "es"

    Scenario: As an owner, I can't edit materiality sectors
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given materiality sector "Hoteles" with id "111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/materiality-sectors/edit/111"
        Then the response status should be 403
