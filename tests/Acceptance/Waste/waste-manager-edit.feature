Feature:
    I want to make sure that the system is allow to access the details of a waste manager

    Scenario: As normal waste manager I can't edit waste managers
        Given account "MyCompany" with id "my-company" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-company" exists
        Given waste manager "Sadeco" with id "sadeco" of account "my-company" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/waste-managers/edit/sadeco"
        Then the response status should be 403

    Scenario: As an owner, I can access to edit waste managers of my account
        Given account "MyCompany" with id "my-company" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-company" exists
        Given waste manager "Sadeco" with id "sadeco" of account "my-company" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/waste-managers/edit/sadeco"
        When I submit a form with "name:OtherSadeco|isPublic:0" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Gestor de residuos actualizado"

    Scenario: As an owner, I can't access to edit waste managers of other accounts
        Given account "MyCompany" with id "my-company" exists
        Given account "OtherCompany" with id "other-company" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-company" exists
        Given waste manager "Sadeco" with id "sadeco" of account "other-company" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/waste-managers/edit/sadeco"
        Then the response status should be 403