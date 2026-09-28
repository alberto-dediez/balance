Feature:
    I want to make sure that the software allows to edit a materiality topic

    Scenario: As an admin, I can edit the base name of a materiality topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics/edit/111"
        When I submit a form with "name:Lavandería subcontratada|scope:scope_3|direction:downstream|position:3" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Categoría de materialidad actualizada con éxito"
        Then materiality topic "111" should have name "Lavandería subcontratada" in locale "es"

    Scenario: As an admin, editing in english creates a translation and keeps the spanish name
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/en/admin/materiality-topics/edit/111"
        When I submit a form with "name:Outsourced laundry|scope:scope_3|direction:upstream|position:3" and button "Save"
        When I follow redirect
        Then the response status should be 200
        Then materiality topic "111" should have name "Outsourced laundry" in locale "en"
        Then materiality topic "111" should have name "Lavandería externa" in locale "es"

    Scenario: As an admin, I can deactivate a materiality topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics/edit/111"
        When I untick the checkbox "update_materiality_topic[active]"
        When I submit a form with "name:Lavandería externa|scope:scope_3|direction:upstream|position:3" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Inactiva"

    Scenario: As an owner, I can't edit materiality topics
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/materiality-topics/edit/111"
        Then the response status should be 403
