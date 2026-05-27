Feature:
    I want to make sure that the system allows to edit a gestor de residuos

    Scenario: As an owner, I can access to edit my gestores de residuos
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given a waste manager "Gestor 1" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/food-waste-managers/edit/333"
        When I submit a form with "name:A new name" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Gestor de residuos actualizado con éxito"

    Scenario: As an admin, I can not access to edit gestores de residuos
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given a waste manager "Gestor 1" with id "333" of account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/food-waste-managers/edit/333"
        Then the response status should be 403
