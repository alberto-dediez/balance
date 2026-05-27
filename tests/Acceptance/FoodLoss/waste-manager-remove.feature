Feature:
    I want to make sure that the system allows to remove a gestor de residuos

    Scenario: As an owner, I can remove my gestores de residuos
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given a waste manager "Gestor 1" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/food-waste-managers/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el gestor de residuos Gestor 1"

    Scenario: As a normal user, I can not remove gestores de residuos
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given account "MyCompany" with id "mycompany" exists
        Given a waste manager "Gestor 1" with id "333" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/food-waste-managers/remove/333"
        Then the response status should be 403
