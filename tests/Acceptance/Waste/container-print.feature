Feature:
    I want to make sure that the system is allow to print the qr of a container

    Scenario: As an owner, I can access to print containers of my account
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given waste "Madera" with id "madera" exists
        Given waste "Plastico" with id "plastico" exists
        Given property "Hotel" with id "hotel" of account "mycompany" exists
        Given container for "madera" in "hotel" with id "333" exists
        Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/containers/print/333"
        Then the response status should be 200

    Scenario: As an owner, I can't access to print qrs of containers of other accounts
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "other" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given waste "Madera" with id "madera" exists
        Given property "Hotel" with id "hotel" of account "other" exists
        Given container for "madera" in "hotel" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/containers/print/333"
        Then the response status should be 403

    Scenario: As an owner, I can access to print all containers of my account
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given waste "Madera" with id "madera" exists
        Given waste "Plastico" with id "plastico" exists
        Given property "Hotel" with id "hotel" of account "mycompany" exists
        Given container for "madera" in "hotel" with id "333" exists
        Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/containers/print-all"
        Then the response status should be 200