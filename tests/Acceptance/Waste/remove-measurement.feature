Feature:
  I want to make sure that the system is allow to remove a measurement

  Scenario: As an owner, I can access to remove measurements
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "pastico" in "hotel" with id "container" exists
    Given measurement for container "container" and account "mycompany" from user "user1" with id "111" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/measurements/remove/111"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado la medición"

  Scenario: As normal user I can't remove measurements
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "pastico" in "hotel" with id "container" exists
    Given measurement for container "container" and account "mycompany" from user "user1" with id "111" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/measurements/remove/111"
    Then the response status should be 403