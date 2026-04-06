Feature:
  I want to make sure that the system is allow to access the details of a donation agreement

  Scenario: As an admin, I can't access to edit donation agreements
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given donation agreement with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/donation-agreements/edit/333"
    Then the response status should be 403

  Scenario: As an owner, I can edit my donation agreements
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given recipient organization "Org 1" with id "org1" of account "mycompany" exists
    Given donation agreement with id "333" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/donation-agreements/edit/333"
    Then the response status should be 200
    When I submit a form with "recipientOrganizationId:org1|startDate:2023-02-01|expirationDate:2024-02-01" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Convenio de donación actualizado con éxito"

  Scenario: As an owner, I cannot access to edit donation agreements of other account
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given donation agreement with id "333" of account "othercompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/donation-agreements/edit/333"
    Then the response status should be 403

  Scenario: As normal user I can't access to edit donation agreements
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given donation agreement with id "333" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/donation-agreements/edit/333"
    Then the response status should be 403
