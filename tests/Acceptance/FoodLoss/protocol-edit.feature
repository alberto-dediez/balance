Feature:
  I want to make sure that the system allows to edit a protocol

  Scenario: As an owner, I can access to edit my protocols
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given protocol "Protocol 1" with id "333" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/protocols/edit/333"
    When I submit a form with "name:A new name|description:Updated description" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Protocolo actualizado con éxito"

  Scenario: As an owner from another account, I cannot edit protocols
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given protocol "Protocol 1" with id "333" of account "othercompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/protocols/edit/333"
    Then the response status should be 403

  Scenario: As a normal user, I cannot edit protocols
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given protocol "Protocol 1" with id "333" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/protocols/edit/333"
    Then the response status should be 403
