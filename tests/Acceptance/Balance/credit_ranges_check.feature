Feature:
    Ability for admins to verify that every received credit lot covers exactly the transferred range of its serial

    Background:
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "999" exists
        Given account "ThirdCompany" with id "888" exists
        Given account "FourthCompany" with id "777" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "100" tonnes exists
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"

    Scenario: Lots received from successive transfers of the same lot have exact ranges
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[888]|tonnes:40" and button "Transferir crédito"
        When I follow redirect
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]|tonnes:20" and button "Transferir crédito"
        When I follow redirect
        When command "balance:credit-ranges:check" is executed with argument "username:admin@dediez.software"
        Then I expect status code 0
        Then I want to see "2 received lot(s) checked, every range is correct." in command output

    Scenario: Lots received from a split between several accounts have exact ranges
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999,888,777]" and button "Transferir crédito"
        When I follow redirect
        When command "balance:credit-ranges:check" is executed with argument "username:admin@dediez.software"
        Then I expect status code 0
        Then I want to see "3 received lot(s) checked, every range is correct." in command output

    Scenario: The check fails when the user does not exist
        When command "balance:credit-ranges:check" is executed with argument "username:nobody@dediez.software"
        Then I expect status code 1
        Then I want to see "nobody@dediez.software" in command output
