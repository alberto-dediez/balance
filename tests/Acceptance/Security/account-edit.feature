Feature:
    I want to make sure that the system is allow to access the details of an account

    Scenario: As an admin, I can access to edit accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/edit/333"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal account I can't edit accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given account "MyCompany" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/accounts/edit/333"
        Then the response status should be 403

    Scenario: As an admin, I can grant a new role to an account
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/edit/333"
        When I submit a form with "name:OtherCompany|active:0" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Cuenta actualizada"

    Scenario: As an admin, I can grant a new role to an account and exit
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/edit/333"
        When I submit a form with "name:OtherCompany|active:0" and button "Guardar y salir"
        Then I am redirected to "/es/admin/accounts"

    Scenario: As an admin, I can assign an emission factor group to an account
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" exists
        Given emission factor group "AR6" with id "444" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/edit/333"
        When I submit a form with "name:MyCompany|active:1|emissionFactorGroupId:444" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Cuenta actualizada"