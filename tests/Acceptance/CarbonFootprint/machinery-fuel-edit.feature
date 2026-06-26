Feature:
    I want to make sure that the system is allow to access the details of a machinery fuel

    Scenario: As an admin, I can access to edit machinery fuels
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given machinery fuel "Maquinaria 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/machinery-fuels/edit/333"
        When I submit a form with "name:A new name|unit:l|machineryType:forestry" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Combustible de maquinaria actualizado con éxito"

    Scenario: As an admin, I see the emission factors table when editing a machinery fuel
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given machinery fuel "Maquinaria 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/machinery-fuels/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"

    Scenario: As an owner, I can't access to edit machinery fuels
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given machinery fuel "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/machinery-fuels/edit/333"
        Then the response status should be 403
