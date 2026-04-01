Feature:
  I want to make sure that the system allow to remove a critical point

  Scenario: As an owner, I can remove my critical points
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given property "My Property" with id "myprop" of account "mycompany" exists
    Given critical point "Item 1" with id "333" of account "mycompany" and property "myprop" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/remove/333"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado el punto crítico Item 1"

  Scenario: As an owner, I can't remove other account's critical points
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given property "Other Property" with id "otherprop" of account "othercompany" exists
    Given critical point "Other CP" with id "444" of account "othercompany" and property "otherprop" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/remove/444"
    Then the response status should be 403
