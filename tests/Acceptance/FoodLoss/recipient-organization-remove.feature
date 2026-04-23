Feature:
    I want to make sure that the system allows to remove an entidad receptora

    Scenario: As an owner, I can remove my entidades receptoras
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given recipient organization "Org 1" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/recipient-organizations/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la entidad receptora Org 1"

    Scenario: As an owner, I cannot remove entidades receptoras of other accounts
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given recipient organization "Other Org" with id "333" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/recipient-organizations/remove/333"
        Then the response status should be 403

    Scenario: As a normal user, I cannot remove entidades receptoras
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given recipient organization "Org 1" with id "333" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/recipient-organizations/remove/333"
        Then the response status should be 403
