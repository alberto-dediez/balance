Feature:
  I want to make sure that the system allow to edit a critical point

  Scenario: As an owner, I can access to edit my critical points
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given property "My Property" with id "myprop" of account "mycompany" exists
    Given critical point "Item 1" with id "333" of account "mycompany" and property "myprop" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/edit/333"
    Then the response status should be 200
    When I submit a form with "name:A new name|propertyId:myprop|supplyChainStage:1" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Punto crítico actualizado con éxito"

  Scenario: As an owner, the edit form has a service category field
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given property "My Property" with id "myprop" of account "mycompany" exists
    Given critical point "Item 1" with id "333" of account "mycompany" and property "myprop" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/edit/333"
    Then the response status should be 200
    Then I see a form field "serviceCategoryIds"

  Scenario: As an owner, I can edit a critical point linking service categories
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given property "My Property" with id "myprop" of account "mycompany" exists
    Given service category "Category A" with id "cat1" exists
    Given critical point "Item 1" with id "333" of account "mycompany" and property "myprop" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/edit/333"
    When I submit a form with "name:A new name|propertyId:myprop|supplyChainStage:1|serviceCategoryIds:[cat1]" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Punto crítico actualizado con éxito"

  Scenario: As an owner, I can't edit other account's critical points
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given property "Other Property" with id "otherprop" of account "othercompany" exists
    Given critical point "Other CP" with id "444" of account "othercompany" and property "otherprop" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/edit/444"
    Then the response status should be 403
