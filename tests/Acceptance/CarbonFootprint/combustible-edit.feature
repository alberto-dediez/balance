Feature:
    I want to make sure that the system is allow to access the details of a combustible

    Scenario: As an admin, I can access to edit combustibles
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given combustible "Combustible 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/combustibles/edit/333"
        When I submit a form with "name:A new name|unit:ud" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Combustible actualizado con éxito"

    Scenario: As an admin, I see the emission factors table when editing a combustible
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given combustible "Combustible 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/combustibles/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"
        Then I see a text "Well-to-tank"

    Scenario: As an owner, I can't access to edit combustibles
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given combustible "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/combustibles/edit/333"
        Then the response status should be 403