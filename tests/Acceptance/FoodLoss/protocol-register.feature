Feature:
    I want to make sure that the software allow to register a protocol

    Scenario: As an admin, I can't access to register new protocols
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/protocols/register"
        Then the response status should be 403

    Scenario: As a normal user, I can't access to register new protocols
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/protocols/register"
        Then the response status should be 403

    Scenario: As an owner, I can access the register form
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/protocols/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As an owner, I can register a new protocol
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/protocols/register"
        Given a dummy PDF file exists in "tests/Acceptance/FoodLoss/test.pdf"
        When I attach the file "tests/Acceptance/FoodLoss/test.pdf" to "file"
        When I submit a form with "name:Manual de buenas prácticas|description:Descripción del protocolo" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Protocolo creado correctamente: Manual de buenas prácticas"
