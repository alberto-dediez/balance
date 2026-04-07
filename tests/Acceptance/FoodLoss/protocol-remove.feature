Feature:
  I want to make sure that the system allows to remove a protocol

  Scenario: As an owner, I can remove my protocols
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given protocol "Protocol 1" with id "333" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/protocols/remove/333"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado el protocolo Protocol 1"

  Scenario: As an owner from another account, I cannot remove protocols
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given protocol "Protocol 1" with id "333" of account "othercompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/protocols/remove/333"
    Then the response status should be 403

  Scenario: As a normal user, I cannot remove protocols
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given protocol "Protocol 1" with id "333" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/protocols/remove/333"
    Then the response status should be 403
