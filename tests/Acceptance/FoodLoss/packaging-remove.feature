Feature:
    I want to make sure that the system is allow to remove a packaging

    Scenario: As an owner, I can remove my packagings
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given packaging "Envase 1" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/packagings/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el envase Envase 1"

    Scenario: As admin I can't remove packagings
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given packaging "Envase 1" with id "333" of account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/packagings/remove/333"
        Then the response status should be 403
