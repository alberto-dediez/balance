Feature:
    I want to make sure that the system allows to edit an entidad receptora

    Scenario: As an owner, I can edit my entidades receptoras
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given recipient organization "Org 1" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/recipient-organizations/edit/333"
        When I submit a form with "name:Updated Name" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Entidad receptora actualizada con éxito"

    Scenario: As an owner, I cannot edit entidades receptoras of other accounts
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given recipient organization "Other Org" with id "333" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/recipient-organizations/edit/333"
        Then the response status should be 403

    Scenario: As an admin, I cannot edit entidades receptoras
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given recipient organization "Org 1" with id "333" of account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/recipient-organizations/edit/333"
        Then the response status should be 403
