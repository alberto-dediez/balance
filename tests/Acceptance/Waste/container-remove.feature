Feature:
    I want to make sure that the system is allow to remove a container

    Scenario: As an owner, I can access to remove containers
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given waste "Madera" with id "madera" exists
        Given property "Hotel" with id "hotel" of account "mycompany" exists
        Given container for "madera" in "hotel" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/containers/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el contenedor"

    Scenario: As normal user I can't remove containers
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_ADMIN" of account "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given waste "Madera" with id "madera" exists
        Given property "Hotel" with id "hotel" of account "mycompany" exists
        Given container for "madera" in "hotel" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/containers/remove/333"
        Then the response status should be 403