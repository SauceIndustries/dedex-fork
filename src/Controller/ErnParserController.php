<?php

/*
 * The MIT License
 *
 * Copyright 2020 Mickaël Arcos <miqwit>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace DedexBundle\Controller;

use DateInterval;
use DateTime;
use DedexBundle\Entity\Ddex;
use DedexBundle\Entity\DdexC\EventDateType as Ern341EventDateType;
use DedexBundle\Entity\EventDateTimeType;
use DedexBundle\Entity\EventDateType;
use DedexBundle\Exception\FileNotFoundException;
use DedexBundle\Exception\RuleValidationException;
use DedexBundle\Exception\VersionNotSupportedException;
use DedexBundle\Exception\XmlLoadException;
use DedexBundle\Exception\XmlParseException;
use DedexBundle\Exception\XsdCompliantException;
use DedexBundle\Rule\Rule;
use Exception;
use ReflectionMethod;
use ReflectionParameter;
use XMLReader;
use DedexBundle\Entity\DdexC\EventDateTimeType as DdexCEventDateTimeType;
use DedexBundle\Entity\Ern41\EventDateTimeType as Ern41EventDateTimeType;
use DedexBundle\Entity\Ern381\EventDateTimeType as Ern381EventDateTimeType;
use DedexBundle\Entity\Ern383\EventDateTimeType as Ern383EventDateTimeType;
use DedexBundle\Entity\Ern382\EventDateTimeType as Ern382EventDateTimeType;
use DedexBundle\Entity\Ern411\EventDateTimeType as Ern411EventDateTimeType;
use DedexBundle\Entity\Ern32\EventDateTimeType as Ern32EventDateTimeType;

/**
 * The main generic parser for XML files. Will load elements one by one
 * while browsing.
 *
 * It uses the xml_parser_create function and xml_parser_* set.
 *
 * Three callbacks are used while browsing the XML file,
 * depending on the encountered event:
 *
 * - callbackCharacterData
 * - callbackEndElement
 * - callbackStartElement
 *
 * On top of the regular parsing, rules are tested against the parsed data
 * to validate them. They are run in the validateRules() function, once
 * the parsing is over.
 *
 * @author Mickaël Arcos <miqwit>
 */
class ErnParserController {

  /**
   * The xml parser as returned by xml_parser_create
   * @var resource
   */
  private $xml_parser = null;

  /**
   * @var string
   */
  private $version = null;

  /**
   * NewReleaseMessage object from the right entity based on version, like:
   *     \DedexBundle\Entity\Ern382\NewReleaseMessage
   * or  \DedexBundle\Entity\Ern41\NewReleaseMessage
   * or  \DedexBundle\Entity\Ern382\PurgeReleaseMessage
   * or  \DedexBundle\Entity\Ern41\PurgeReleaseMessage
   */
  private $ern = null;

  /**
   * Stack of elements being parsed. Each entry is ['tag' => string, 'element' => object]
   * Maintains insertion order for reliable LIFO operations
   * @var array
   */
  private $pile = [];

  /**
   * Contains a list of ids (spl_object_id) of objects that had a closed
   * element, so they are not reused for data erasement.
   *
   * @var array
   */
  private $closed_objects = [];

  /**
   * The path of the file being parsed
   * @var string
   */
  private $file_path = null;

  /**
   * Cache for method_exists results: [class::method => bool]
   * @var array
   */
  private $method_exists_cache = [];

  /**
   * Cache for ReflectionMethod instances: [class::method => ReflectionMethod]
   * @var array
   */
  private $reflection_cache = [];

  /**
   * Cache for normalized tag names: [original => normalized]
   * @var array
   */
  private $namespace_cache = [];

  /**
   * Cache for type lookups: [class::tag => type]
   * @var array
   */
  private $type_cache = [];

  /**
   * Cache for function name lists: [prefix::tag => [names]]
   * @var array
   */
  private $function_names_cache = [];

  /**
   * If true, display logs (for debugging purpose mainly)
   * @var bool
   */
  private $display_log = false;

  /**
   * If true, will validate XML against XSD
   * @var type
   */
  private $xsd_validation = false;

  /**
   * List of rules applied to this parser to validate
   * @var Rule[]
   */
  private $rules = array();

  /**
   * Messages from rules
   * @var string[]
   */
  private $rule_messages = [Rule::LEVEL_ERROR => [], Rule::LEVEL_WARNING => []];

  /**
   * Array of arrays (by depth level) of attributes to process.
   * Process the attributes of the current level when we leave the level (element ends)
   * @var type
   */
  private $attrs_to_process = [];

  /**
   * Last element handled by the character_data_handler callback with the following shape
   * [element, tag, value]
   *
   * @var array
   */
  private $lastElement = [];

  /**
   * Tracks when we're inside a list container that uses addTo* methods.
   * Format: ['listTag' => 'InstantGratificationResourceList', 'parent' => $parentObject]
   * @var array|null
   */
  private $current_list_context = null;

  /**
   * Log something (will echo if display_log is true)
   * @param type $message
   */
  private function log($message) {
    if ($this->display_log) {
      // Use error_log so it goes to PHP error log (works in both CLI and web contexts)
      error_log($message);
      // Also echo for CLI compatibility
      echo $message . "\n";
    }
  }

  /**
   * Add a new rule to validate
   * @param Rule $rule
   */
  public function addRule(Rule $rule) {
    $this->rules[] = $rule;
  }

  public function addRuleSet(array $rules) {
    foreach ($rules as $rule) {
      $this->addRule($rule);
    }
  }

  /**
   * Activate or deactivate logs
   * @param bool $val
   */
  public function setDisplayLog(bool $val) {
    $this->display_log = $val;
  }

  /**
   * Activate or deactivate XSD validation
   * @param bool $val
   */
  public function setXsdValidation(bool $val) {
    $this->xsd_validation = $val;
  }

  /**
   * Indicates that the next parsed data must be attached to the parent and not
   * as a new object.
   * @var bool
   */
  private bool $set_to_parent = false;

  /**
   * Indicates the tag name under which the next parsed data must be attached to
   * the parent. If it's isBackFill, then will attach to $parent->setIsBackFill().
   * This property is used to guess the function name.
   *
   * @var type
   */
  private string $set_to_parent_tag = "";

  /**
   * Indicates that we're handling a nested <Extent> element inside an ExtentType.
   * This is for incorrectly nested XML structures like <ImageHeight><Extent>300</Extent></ImageHeight>
   * Enabled for all ERN versions.
   * @var bool
   */
  private bool $handling_nested_extent = false;

  /**
   * Stores the UnitOfMeasure attribute from a nested <Extent> element
   * @var string|null
   */
  private ?string $nested_extent_unit_of_measure = null;

  /**
   * Stores the accumulated value for a nested <Extent> element (for handling split values)
   * @var string
   */
  private string $nested_extent_value = "";

  /**
   * These tags and attributes should not be parsed like the other ones.
   * @var array
   */
  private array $ignore_these_tags_or_attributes = [
      "xmlns:ern",
      "xmlns:ernm",
      "xmlns:xs",
      "xmlns:xsi",
      "xs:schemaLocation",
      "xsi:schemaLocation",
      "xmlns:avs",
      // FUGA proprietary extension (not in DDEX ERN MessageHeader schema)
      "SkipChecksum",
  ];

  /**
   * Depth of ignored elements currently open (skip character data while > 0).
   * @var int
   */
  private int $ignored_element_depth = 0;

  /**
   * Function called when the parser encounters a tag opening
   *
   * @param type $parser
   * @param string $name The name of the tag (may contain namespace prefix)
   * @param array $attrs The attributes of the tag
   */
  private function callbackStartElement($parser, string $name, array $attrs) {
    // Check ignore list first (contains attribute names like "xmlns:ern" that should not be normalized)
    if (in_array($name, $this->ignore_these_tags_or_attributes)) {
      $this->ignored_element_depth++;
      return;
    }

    // Strip namespace prefix from tag name (e.g., "ern:NewReleaseMessage" -> "NewReleaseMessage")
    // This normalizes all tag names consistently, not just root elements
    $name = $this->stripNamespacePrefix($name);

    // Create element here. But to know its type, call its parent getter if any.
    // There is only one case when there is no parent: NewReleaseMessage or PurgeReleaseMessage.
    if ($name === "NewReleaseMessage" || $name === "PurgeReleaseMessage") {
      $class_name = "DedexBundle\\Entity\\Ern{$this->version}\\$name";
      $this->ern = new $class_name();
    } else {
      $parent = $this->getLastPileElement();
      if ($parent === null) {
        // This shouldn't happen, but handle gracefully
        throw new Exception("No parent element found in pile for tag: $name");
      }
      $parent_class = get_class($parent);

      // Special handling for incorrectly nested <Extent> elements inside ExtentType
      // This handles malformed XML like <ImageHeight><Extent>300</Extent></ImageHeight>
      // Enabled for all ERN versions
      if ($name === "Extent" && $this->isExtentType($parent_class)) {
        $this->handling_nested_extent = true;
        // Store UnitOfMeasure attribute if present
        $this->nested_extent_unit_of_measure = $attrs['UnitOfMeasure'] ?? null;
        // Initialize value accumulator
        $this->nested_extent_value = "";
        return; // Don't create a new object, we'll handle this specially
      }

      // If we're inside a list context, handle child elements specially
      if ($this->current_list_context !== null) {
        // This is a child element of a list (like DealResourceReference inside InstantGratificationResourceList)
        // Don't create an object or set set_to_parent - the value will be handled directly
        // in setCurrentElement using the list context's addTo* method
        return; // Don't create an object, we'll handle the value in setCurrentElement
      }

      // Try to find the correct parent class by searching backwards through the pile
      // This handles cases where the immediate parent might not have the method
      // (e.g., TechnicalDetails should be on SoundRecording, not ResourceList)
      $func_names = $this->listPossibleFunctionNames("get", $name);
      $found_parent_class = $parent_class;
      $found = false;
      
      // Check if the immediate parent has the method
      foreach ($func_names as $func_name) {
        if ($this->cachedMethodExists($parent_class, $func_name)) {
          $found = true;
          break;
        }
      }
      
      // If not found, search backwards through the pile
      if (!$found && count($this->pile) > 1) {
        for ($i = count($this->pile) - 2; $i >= 0; $i--) {
          $candidate = $this->pile[$i]['element'];
          $candidate_class = get_class($candidate);
          foreach ($func_names as $func_name) {
            if ($this->cachedMethodExists($candidate_class, $func_name)) {
              $found_parent_class = $candidate_class;
              $found = true;
              break 2;
            }
          }
        }
      }

      $class_name = $this->getTypeOfElementFromDoc($found_parent_class, $name);

      if (!class_exists($class_name)) {
        // Check if this is a list container (array type) that uses addTo* methods
        // This pattern is used in ERN 41, 43, and 411 for elements like InstantGratificationResourceList
        // which are defined as array<string> with xml_list inline: false
        // Other ERN versions (32, 371, 381, 382, 383) use class types (e.g. DealResourceReferenceListType)
        // so class_exists() will return true for them and this code won't execute
        $add_to_method = "addTo" . $name;
        
        // Use the found parent (which has the method) instead of the immediate parent
        $found_parent = null;
        if ($found_parent_class !== $parent_class) {
          // Find the actual object with the found parent class
          for ($i = count($this->pile) - 1; $i >= 0; $i--) {
            if (get_class($this->pile[$i]['element']) === $found_parent_class) {
              $found_parent = $this->pile[$i]['element'];
              break;
            }
          }
        }
        $parent_to_check = $found_parent !== null ? $found_parent : $parent;
        
        if ($this->cachedMethodExists($parent_to_check, $add_to_method)) {
          // This is a list container, set up the context
          $this->current_list_context = [
            'listTag' => $name,
            'parent' => $parent_to_check,
            'addToMethod' => $add_to_method
          ];
          return; // Don't create an object, we'll handle entries directly
        }
        
        // Set element to parent class
        $this->set_to_parent = true;
        $this->set_to_parent_tag = $name;
        return; // no attributes in this case
      }
    }
    $elem = $this->instanciateClass($class_name);

    // Push element to stack (no need to check for duplicates - stack handles them naturally)
    array_push($this->pile, ['tag' => $name, 'element' => $elem]);

    // Will process attributes later
    $this->attrs_to_process[count($this->pile)] = $attrs;
  }

  /**
   * From a class name, instanciate it with a parameter null.
   * DDEX classes for simple elements take the text value as a parameter.
   * When we create an element (callbackStartElement), we don't have access to that
   * textual value yet, so we pass null and the actual valu will be set later
   * (in the callbackCharacterData).
   *
   * Handles a special case for DateInterval which needs a specific string for
   * construction. Instanciate it with 0 seconds.
   *
   * @param type $class_name
   * @return DateInterval|\DedexBundle\Controller\class_name
   */
  private function instanciateClass($class_name) {
    // Safety check: remove any array notation that might have slipped through
    $class_name = preg_replace("/\[\]/", "", $class_name);
    
    if ($class_name === "\DateInterval") {
      // For DateInterval can't instanciate with null
      return new DateInterval("PT0M0S");  // will be erased
    }

    if ($class_name === '\DateTime') {
      return new $class_name('');
    }

    if (is_subclass_of($class_name, EventDateType::class) || is_subclass_of($class_name, EventDateTimeType::class)) {
        $dt = new DateTime();
        return new $class_name($dt);
    }

    if ($class_name === "\DedexBundle\Entity\Ern382\EventDateTimeType") {
      return new \DedexBundle\Entity\Ern382\EventDateTimeType(new \DateTime()); // TODO
    }

    if ($class_name === "\DedexBundle\Entity\Ern32\EventDateTimeType") {
      return new \DedexBundle\Entity\Ern32\EventDateTimeType(new \DateTime());
    }

    if ($class_name === "\DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType") {
      return new \DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType(new \DateTime()); // TODO
    }

    return new $class_name(null);
  }

  /**
   * Function called when the parser encounters a tag ending.
   * Will process attributes of the current element (last in pile) and attach it
   * to its parent. Will delete this element (contained in the parent now).
   *
   * @param type $parser
   * @param string $name The name of the tag (may contain namespace prefix)
   */
  private function callbackEndElement($parser, string $name) {
    // Check ignore list first (contains attribute names like "xmlns:ern" that should not be normalized)
    if (in_array($name, $this->ignore_these_tags_or_attributes)) {
      $this->ignored_element_depth = max(0, $this->ignored_element_depth - 1);
      return;
    }

    // Strip namespace prefix from tag name for consistency
    $name = $this->stripNamespacePrefix($name);

    // Special handling for nested <Extent> elements
    if ($this->handling_nested_extent && $name === "Extent") {
      // Always reset flags first to prevent leaving parser in inconsistent state
      $this->handling_nested_extent = false;
      $unit_of_measure = $this->nested_extent_unit_of_measure;
      $accumulated_value = $this->nested_extent_value;
      $this->nested_extent_unit_of_measure = null;
      $this->nested_extent_value = "";
      
      $parent = $this->getLastPileElement();
      if ($parent && $this->isExtentType(get_class($parent))) {
        // Set the accumulated value on the ExtentType parent
        $value_clean = trim($accumulated_value);
        if ($value_clean !== "" && is_numeric($value_clean)) {
          $parent->value((float)$value_clean);
        } else if ($value_clean !== "") {
          // Log warning for non-numeric values (but don't throw error to maintain backward compatibility)
          $this->log("Warning: Non-numeric value found in nested Extent element: " . $value_clean);
        }
        // Set UnitOfMeasure attribute if it was present on the nested Extent element
        if ($unit_of_measure !== null) {
          $parent->setUnitOfMeasure($unit_of_measure);
        }
        return; // Don't process this element further
      } else {
        // Safety: If parent is not ExtentType, log warning but flags are already reset
        $this->log("Warning: Nested Extent element closed but parent is not ExtentType");
        return; // Still return to avoid normal processing
      }
    }

    // Special handling for elements inside list contexts (like DealResourceReference inside InstantGratificationResourceList)
    // List containers themselves are not added to the pile, so we need special handling
    if ($this->current_list_context !== null) {
      if ($name === $this->current_list_context['listTag']) {
        // This is the list container element itself ending
        // It was never added to the pile, so just reset the context and return
        $this->current_list_context = null;
        return;
      } else {
        // This is a child element of the list, not the list itself
        // The value was already handled in setCurrentElement, so just return
        return;
      }
    }

    $last_element = $this->getLastPileElement();
    $properties = $last_element ? array_filter(array_values((array) $last_element)) : [];
    // Only remove empty elements if they are list containers (end with "List" or are known list types)
    // Don't remove resource elements like SoundRecording, Image, etc. even if they appear empty,
    // as they may have child elements that haven't been processed yet
    $is_list_container = (substr($name, -4) === "List") || 
                         (substr($name, -13) === "ReferenceList") ||
                         (substr($name, -8) === "ListType");
    
    if (count($properties) == 0 && !$this->set_to_parent && $is_list_container) {
      // If we are leaving an element that is completely empty (the object
      // at the end of the pile, converted to an array, only contains emtpy
      // values), then to not add this element to parent. It's an empty List
      // element in DDEX, like ReleaseResourceReferenceList
      // Only do this for list containers, not for resource elements
      array_pop($this->pile);
    } else if (!$this->set_to_parent) {
      // Process attributes now.
      // Consider each attribute as a setter of current element
      // if attribute is MessageSchemaVersionId on ern:NewReleaseMessage
      // then will call $this->ern->setMESSAGESCHEMAVERSIONID
      if (array_key_exists(count($this->pile), $this->attrs_to_process)) {
        foreach ($this->attrs_to_process[count($this->pile)] as $key => $val) {
          if (in_array($key, $this->ignore_these_tags_or_attributes)) {
            continue;
          }

          $this->callbackStartElement($parser, $key, []);
          $this->callbackCharacterData($parser, $val);
          $this->callbackEndElement($parser, $key);
        }

        array_pop($this->attrs_to_process);
      }

      $this->attachToParent();

      if ($name !== 'ResourceMusicalWorkReference') {
          array_pop($this->pile);
      }
    }

    // Reset parent setting
    $this->set_to_parent = false;
    $this->set_to_parent_tag = "";

    // Note: List context is now reset earlier when the list element ends (see above)

    // Reset last element
    $this->lastElement = [];
  }

  /**
   * Check if a class is an ExtentType (from any ERN version)
   *
   * @param string $class_name
   * @return bool
   */
  private function isExtentType(string $class_name): bool {
    // Check for ExtentType in various namespaces
    $extent_type_patterns = [
      'DedexBundle\\Entity\\Ern411\\ExtentType',
      'DedexBundle\\Entity\\Ern41\\ExtentType',
      'DedexBundle\\Entity\\Ern43\\ExtentType',
      'DedexBundle\\Entity\\Ern382\\ExtentType',
      'DedexBundle\\Entity\\Ern381\\ExtentType',
      'DedexBundle\\Entity\\Ern383\\ExtentType',
      'DedexBundle\\Entity\\Ern341\\ExtentType',
      'DedexBundle\\Entity\\Ern371\\ExtentType',
      'DedexBundle\\Entity\\Ern37\\ExtentType',
      'DedexBundle\\Entity\\Ern32\\ExtentType',
      'DedexBundle\\Entity\\DdexC\\ExtentType',
    ];

    return in_array($class_name, $extent_type_patterns) || 
           substr($class_name, -10) === 'ExtentType';
  }

  /**
   * Attach the last element in pile to the one above it.
   * Guess the function of the parent to attach the child.
   * If only one element, set it to this->ern and return. This is the end
   * of the parsing.
   *
   * @return type
   */
  private function attachToParent() {
    if (count($this->pile) < 2) {
      // We are done, attach it to $this->ern
      $this->ern = $this->getLastPileElement();
      return;
    }

    $last_entry = $this->getLastPileEntry();
    $child_tag = $last_entry['tag'];
    $child = $last_entry['element'];

    $pile_count = count($this->pile);
    $immediate_parent_index = $pile_count - 2; // Parent is second-to-last
    $immediate_parent = isset($this->pile[$immediate_parent_index]) && isset($this->pile[$immediate_parent_index]['element']) 
      ? $this->pile[$immediate_parent_index]['element'] 
      : null;
    
    $func_name = null;
    $parent = null;
    
    // Priority 1: Check immediate parent first
    // This ensures properties attach to their immediate parent rather than an ancestor
    // (e.g., ReleaseResourceReference on ResourceGroupContentItem, not on Release)
    if ($immediate_parent !== null && is_object($immediate_parent)) {
      // First, try addTo* methods (for list properties)
      $add_to_methods = ["addTo" . $child_tag, "addTo" . $child_tag . "List"];
      foreach ($add_to_methods as $add_to_method) {
        if ($this->cachedMethodExists($immediate_parent, $add_to_method)) {
          $immediate_parent->$add_to_method($child);
          return;
        }
      }
      
      // Then, try set* methods, but only if they don't expect an array (single-value properties)
      // Check exact match first, then plural, then List (List methods are more likely to expect arrays)
      $set_methods = ["set" . $child_tag, "set" . $child_tag . "s", "set" . $child_tag . "List"];
      foreach ($set_methods as $set_method) {
        if ($this->cachedMethodExists($immediate_parent, $set_method)) {
          // Check if the set* method expects an array - if so, it's a list property and we should skip it
          // (we already checked addTo* above, so if we get here and it expects array, something is wrong)
          $reflection_key = get_class($immediate_parent) . '::' . $set_method;
          if (!isset($this->reflection_cache[$reflection_key])) {
            try {
              $this->reflection_cache[$reflection_key] = new ReflectionMethod($immediate_parent, $set_method);
            } catch (\ReflectionException $e) {
              // If reflection fails, skip this method
              continue;
            }
          }
          $rm = $this->reflection_cache[$reflection_key];
          $params = $rm->getParameters();
          if (count($params) > 0) {
            $param = $params[0];
            $type = $param->getType();
            // If the parameter type is "array", this is a list property - skip it
            // (we should have found addTo* method above)
            if ($type !== null) {
              // For PHP 7.2+, getType() returns ReflectionNamedType or ReflectionUnionType
              // For PHP 8.0+, we need to check if it's a named type
              // Handle both ReflectionNamedType and ReflectionUnionType
              $type_name = null;
              if (is_string($type)) {
                // PHP 7.0-7.3: getType() can return a string
                $type_name = $type;
              } elseif (method_exists($type, 'getName')) {
                // PHP 7.4+: ReflectionNamedType
                $type_name = $type->getName();
              } elseif (method_exists($type, '__toString')) {
                // Fallback: try to convert to string
                $type_name = (string)$type;
              }
              // Check if it's an array type (could be "array" or a union type containing array)
              if ($type_name === 'array') {
                continue; // Skip this set* method, it's for lists
              }
              // For PHP 8.0+ union types, check if array is one of the types
              if (method_exists($type, 'getTypes')) {
                $union_types = $type->getTypes();
                foreach ($union_types as $union_type) {
                  $union_type_name = null;
                  if (is_string($union_type)) {
                    $union_type_name = $union_type;
                  } elseif (method_exists($union_type, 'getName')) {
                    $union_type_name = $union_type->getName();
                  }
                    if ($union_type_name === 'array') {
                      continue 2; // Skip this set* method, it's for lists
                    }
                }
              }
            }
          }
          // This set* method doesn't expect an array, so it's a single-value property - use it
          $immediate_parent->$set_method($child);
          return;
        }
      }
    }
    
    // Priority 3: Search backwards (only if immediate parent has no matching method)
    // This handles cases where the immediate parent might not have the method
    // (e.g., TechnicalDetails should be on SoundRecording, not ResourceList)
    $start_index = $immediate_parent_index - 1; // Start from element before immediate parent
    
    // First, try addTo* methods when searching backwards (for list properties)
    $add_to_methods = ["addTo" . $child_tag, "addTo" . $child_tag . "List"];
    for ($i = $start_index; $i >= 0; $i--) {
      if (!isset($this->pile[$i]) || !isset($this->pile[$i]['element'])) {
        continue;
      }
      $elem = $this->pile[$i]['element'];
      if (!is_object($elem)) {
        continue;
      }
      foreach ($add_to_methods as $add_to_method) {
        if ($this->cachedMethodExists($elem, $add_to_method)) {
          $func_name = $add_to_method;
          $parent = $elem;
          break 2;
        }
      }
    }
    
    // If addTo* not found, try set* methods when searching backwards (for single-value properties)
    // But only if they don't expect an array
    if ($func_name === null) {
      $set_methods = ["set" . $child_tag, "set" . $child_tag . "s", "set" . $child_tag . "List"];
      for ($i = $start_index; $i >= 0; $i--) {
        if (!isset($this->pile[$i]) || !isset($this->pile[$i]['element'])) {
          continue;
        }
        $elem = $this->pile[$i]['element'];
        if (!is_object($elem)) {
          continue;
        }
        foreach ($set_methods as $set_method) {
          if ($this->cachedMethodExists($elem, $set_method)) {
            // Check if the set* method expects an array - if so, skip it (it's a list property)
            $reflection_key = get_class($elem) . '::' . $set_method;
            if (!isset($this->reflection_cache[$reflection_key])) {
              try {
                $this->reflection_cache[$reflection_key] = new ReflectionMethod($elem, $set_method);
              } catch (\ReflectionException $e) {
                // If reflection fails, skip this method
                continue;
              }
            }
            $rm = $this->reflection_cache[$reflection_key];
            $params = $rm->getParameters();
            if (count($params) > 0) {
              $param = $params[0];
              $type = $param->getType();
              // If the parameter type is "array", this is a list property - skip it
              if ($type !== null) {
                // For PHP 7.2+, getType() returns ReflectionNamedType or ReflectionUnionType
                // For PHP 8.0+, we need to check if it's a named type
                // Handle both ReflectionNamedType and ReflectionUnionType
                $type_name = null;
                if (is_string($type)) {
                  // PHP 7.0-7.3: getType() can return a string
                  $type_name = $type;
                } elseif (method_exists($type, 'getName')) {
                  // PHP 7.4+: ReflectionNamedType
                  $type_name = $type->getName();
                } elseif (method_exists($type, '__toString')) {
                  // Fallback: try to convert to string
                  $type_name = (string)$type;
                }
                // Check if it's an array type (could be "array" or a union type containing array)
                if ($type_name === 'array') {
                  continue; // Skip this set* method, it's for lists
                }
                // For PHP 8.0+ union types, check if array is one of the types
                if (method_exists($type, 'getTypes')) {
                  $union_types = $type->getTypes();
                  foreach ($union_types as $union_type) {
                    $union_type_name = null;
                    if (is_string($union_type)) {
                      $union_type_name = $union_type;
                    } elseif (method_exists($union_type, 'getName')) {
                      $union_type_name = $union_type->getName();
                    }
                    if ($union_type_name === 'array') {
                      continue 2; // Skip this set* method, it's for lists
                    }
                  }
                }
              }
            }
            // This set* method doesn't expect an array, so it's a single-value property - use it
            $func_name = $set_method;
            $parent = $elem;
            break 2;
          }
        }
      }
    }
    
    // If still not found, fall back to getValidFunctionName (for create* methods, etc.)
    if ($func_name === null) {
      [$func_name, $parent] = $this->getValidFunctionName("set", $child_tag, $child);
    }
    
    $parent->$func_name($child);
  }

  /**
   * Cache-aware method_exists check
   * 
   * @param string|object $class Class name or object
   * @param string $method Method name
   * @return bool
   */
  private function cachedMethodExists($class, $method) {
    $class_name = is_object($class) ? get_class($class) : $class;
    $cache_key = $class_name . '::' . $method;
    if (!isset($this->method_exists_cache[$cache_key])) {
      $this->method_exists_cache[$cache_key] = method_exists($class, $method);
    }
    return $this->method_exists_cache[$cache_key];
  }

  /**
   * Strip namespace prefix from tag name (e.g., "ern:NewReleaseMessage" -> "NewReleaseMessage")
   * 
   * @param string $tag Tag name that may contain namespace prefix
   * @return string Tag name without namespace prefix
   */
  private function stripNamespacePrefix(string $tag): string {
    // Check cache first
    if (isset($this->namespace_cache[$tag])) {
      return $this->namespace_cache[$tag];
    }
    
    // Remove common namespace prefixes (ern:, ernm:, etc.)
    // Pattern: any word characters followed by colon at the start
    if (preg_match('/^[a-zA-Z0-9_]+:(.+)$/', $tag, $matches)) {
      $normalized = $matches[1];
    } else {
      $normalized = $tag;
    }
    
    // Cache the result
    $this->namespace_cache[$tag] = $normalized;
    return $normalized;
  }

  /**
   * Get the last entry from the pile stack
   * @return array|null ['tag' => string, 'element' => object] or null if empty
   */
  private function getLastPileEntry() {
    if (empty($this->pile)) {
      return null;
    }
    return end($this->pile);
  }

  /**
   * Get the last element from the pile stack
   * @return object|null The last element object or null if empty
   */
  private function getLastPileElement() {
    $entry = $this->getLastPileEntry();
    return $entry ? $entry['element'] : null;
  }

  /**
   * Get the last tag name from the pile stack
   * @return string|null The last tag name or null if empty
   */
  private function getLastPileTag() {
    $entry = $this->getLastPileEntry();
    return $entry ? $entry['tag'] : null;
  }

  /**
   * Find an element in the pile by tag name, searching backwards from the end
   * @param string $tag The tag name to search for
   * @return object|null The element object or null if not found
   */
  private function findInPile(string $tag) {
    for ($i = count($this->pile) - 1; $i >= 0; $i--) {
      if ($this->pile[$i]['tag'] === $tag) {
        return $this->pile[$i]['element'];
      }
    }
    return null;
  }

  /**
   * Return a clean version of tag with symbols compatible with function names.
   * : becomes _
   *
   * @param string $tag string to clean
   * @return string
   */
  public function cleanTag($tag) {
    return str_replace(":", "_", $tag);
  }

  /**
   * Guess functions for a getter or a setter in the DDEX classes.
   *
   * @param type $prefix "get" or "set"
   * @param type $tag
   * @return type
   */
  private function listPossibleFunctionNames($prefix, $tag) {
    // Check cache first
    $cache_key = $prefix . '::' . $tag;
    if (isset($this->function_names_cache[$cache_key])) {
      return $this->function_names_cache[$cache_key];
    }
    
    // It's possible this script added a ##\d+ information at the end of
    // the tag to avoid key duplicate. Remove it here.
    if (strpos($tag, "##") !== false) {
      $tag = preg_replace("/##\d+/", "", $tag);
    }

    $func_names = [];

    switch ($prefix) {
      case "get":
        $func_names[] = $prefix . $tag;
        $func_names[] = $prefix . $tag . "s";
        $func_names[] = $prefix . $tag . "List";
      //        $prefix . $tag . "Type",

        // Hack for release deals. DDEX 4.1.1 is not consistent. It has a DealList
        // and ReleaseDeals in it. This parser would expect a ReleaseDealList instead
        // of the actual DealList
        if ($tag == "ReleaseDeal") {
          $func_names[] = $prefix . "DealList";
        }

        break;
      case "set":
        // order is important - try set* first for single values, addTo* for lists
        $func_names[] = $prefix . $tag;
        $func_names[] = $prefix . $tag . "s";
        $func_names[] = $prefix . $tag . "List";
        $func_names[] = "addTo" . $tag;
        $func_names[] = "addTo" . $tag . "List";
        $func_names[] = "create" . $tag;
        $func_names[] = "create" . $tag . "List";

        // Hack for release deals. DDEX 4.1.1 is not consistent. It has a DealList
        // and ReleaseDeals in it. This parser would expect a ReleaseDealList instead
        // of the actual DealList
        if ($tag == "ReleaseDeal") {
          $func_names[] = "addToDealList";
        }

        break;
      default:
        throw new \Exception("Prefix must be get or set");
    }

    // Cache before returning
    $this->function_names_cache[$cache_key] = $func_names;
    return $func_names;
  }

  /**
   * From the current pile of tag, set the value to the last element.
   * If the pile is ['ERN:NEWRELEASEMESSAGE', 'MESSAGEHEADER',
   * 'MESSAGERECIPIENT', 'PARTYID'], then will call
   * $this->ern->getMESSAGEHEADER()->getMESSAGERECIPIENT()->setPARTYID($value)
   * 
   * @param string $value Value to set
   */
  private function setCurrentElement($value) {
    // Check if we're inside a list context that uses addTo* methods
    if ($this->current_list_context !== null) {
      $value_clean = trim($value);
      if ($value_clean !== "") {
        $pile_tags = array_column($this->pile, 'tag');
        $this->log($value_clean . ": " . implode("->", $pile_tags) . " (adding to " . $this->current_list_context['listTag'] . ")");
        // Use the addTo* method directly
        $add_to_method = $this->current_list_context['addToMethod'];
        $parent = $this->current_list_context['parent'];
        $parent->$add_to_method($value_clean);
      }
      return;
    }

    // Special handling for complex types with simpleContent (like ReleaseResourceReferenceType)
    // These types have a value() method to set the text content directly on the object
    // We should set the value on the current element, not create a new object
    if (!$this->set_to_parent && count($this->pile) > 0) {
      $current_element = $this->getLastPileElement();
      if ($current_element !== null && is_object($current_element) && method_exists($current_element, 'value')) {
        // If the previous element was the same, concatenate value
        // xml_parser is known to split values when encountering multibyte chars
        $current_tag = $this->getLastPileTag();
        if (!empty($this->lastElement) && $this->lastElement[0] === $current_element && $this->lastElement[1] === $current_tag) {
          $value = $this->lastElement[2] . $value;
        }
        $value_clean = trim($value);
        if ($value_clean !== "") {
          if ($this->display_log) {
            $pile_tags = array_column($this->pile, 'tag');
            $this->log($value_clean . ": " . implode("->", $pile_tags) . " (setting value on current element)");
          }
          // Centralized date formatting: format date strings before setting value
          // This ensures all date types get consistent formatting regardless of ERN version
          $value_clean = $this->formatDateValueForObject($current_element, $value_clean);
          
          // For DateTime-based types, use reflection to set formatted string directly
          // This avoids needing entity class modifications
          $class_name = get_class($current_element);
          if (is_subclass_of($class_name, EventDateTimeType::class) ||
              $this->isEventDateTimeWithoutFlagsType($class_name) ||
              $class_name === 'DedexBundle\\Entity\\DdexC\\EventDateType') {
            // Parse the string to DateTime, then set formatted string via reflection
            try {
              $dt = $this->parseDateString($value_clean);
              if ($dt !== null) {
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $isDateTimeType = is_subclass_of($class_name, EventDateTimeType::class) ||
                                  $this->isEventDateTimeWithoutFlagsType($class_name);
                $this->setFormattedDateValue($current_element, $dt, $isDateTimeType);
                // Also call value() to maintain compatibility (stores DateTime internally)
                $current_element->value($dt);
              }
            } catch (\Exception $e) {
              // Fallback to direct value() call if parsing fails
              $current_element->value($value_clean);
            }
          } else {
            // For string-based types, just set the value directly
            $current_element->value($value_clean);
          }
          $this->lastElement = [$current_element, $current_tag, $value];
        }
        return; // Don't process further - value is set on current element
      }
    }

    // Use last element in pile
    $pile_count = count($this->pile);

    if ($this->set_to_parent) {
      $elem = $this->getLastPileElement();
      $tag = $this->set_to_parent_tag;
    } else {
      // Get parent element (second-to-last) and current tag (last)
      if ($pile_count >= 2) {
        $elem = $this->pile[$pile_count - 2]['element'];
        $tag = $this->getLastPileTag();
      } else {
        $elem = $this->getLastPileElement();
        $tag = $this->getLastPileTag();
      }
    }
    // If the previous element was the same and had the same tag, concatenate value
    // xml_parser is known to split values when encountering multibyte chars and call the character_data_handler multiple times
    if (!empty($this->lastElement) && $this->lastElement[0] === $elem && $this->lastElement[1] === $tag) {
      $value = $this->lastElement[2] . $value;
    }
    $value_clean = trim($value);
    if ($this->display_log) {
      $pile_tags = array_column($this->pile, 'tag');
      $this->log($value_clean . ": " . implode("->", $pile_tags));
    }
    [$func_name, $elem] = $this->getValidFunctionName("set", $tag, $elem);

    // It's possible we're trying to set a text but it's expecting an
    // object (where text should be placed in value).
    $value_inst = $this->instanciateTypeFromDoc($elem, $func_name, $value_clean);
    
    // If parsing returned null (empty/invalid value), skip setting the value
    if ($value_inst === null) {
      return;
    }

    $this->lastElement = [$elem, $tag, $value];

    if ($this->set_to_parent) {
      $elem->$func_name($value_inst);
    } else {
      // Update the last element in the stack
      $last_index = count($this->pile) - 1;
      if ($last_index >= 0) {
        $this->pile[$last_index]['element'] = $value_inst;
      }
    }
  }

  /**
   * Tries all funtion of listPossibleFunctionNames on the current element and
   * return the first one that exists.
   * If none exist, throws an exception
   *
   * @param string $prefix "set" or "get"
   * @param type $tag
   * @return type
   * @throws Exception
   */
  private function getValidFunctionName($prefix, $tag, $value = null) {
    $func_names = $this->listPossibleFunctionNames($prefix, $tag);

    $pile_count = count($this->pile);
    $start_index = $pile_count - 1;

    // If type is complex, always start at previous than end,
    // as end will be itself
    if ($value != null && !$this->set_to_parent && is_object($value) && !in_array(get_class($value), ["string", "int", "bool", "float", "mixed"])) {
      $start_index = $pile_count - 2;
    }

    $i = $start_index;
    while ($i >= 0) {
      // Safety check: ensure pile entry exists and has element
      if (!isset($this->pile[$i]) || !isset($this->pile[$i]['element'])) {
        $i--;
        continue;
      }
      
      $elem = $this->pile[$i]['element'];
      
      // Safety check: ensure element is an object
      if (!is_object($elem)) {
        $i--;
        continue;
      }
      
      $function_used = false;
      foreach ($func_names as $func_name) {
        if (!$this->cachedMethodExists($elem, $func_name)) {
          continue;
        }
        $function_used = true;
        return array($func_name, $elem);
      }

      // Continue with previous element if exists
      $i--;
    }

    // No function found
    $fileInfo = $this->file_path ? " File: {$this->file_path}" : "";
    $pile_tags = array_column($this->pile, 'tag');
    throw new Exception("No functions found for this tag: $tag. Path is " . implode(",", $pile_tags) . $fileInfo);
  }


  /**
   * From the doc of a class and function (guessed from $tag), return the type
   * as a string of the element.
   *
   * @param type $class
   * @param type $tag
   * @return string
   */
  private function getTypeOfElementFromDoc($class, $tag) {
    // Check cache first
    $class_name = is_object($class) ? get_class($class) : $class;
    $cache_key = $class_name . '::' . $tag;
    if (isset($this->type_cache[$cache_key])) {
      return $this->type_cache[$cache_key];
    }
    
    // First, try to find the method on the provided parent class
    // This is important for maintaining context when the parent might not be at the end of the pile
    $func_names = $this->listPossibleFunctionNames("get", $tag);
    $function_name = null;
    $found_class = null;
    
    // Check the provided parent class first
    foreach ($func_names as $func_name) {
      if ($this->cachedMethodExists($class, $func_name)) {
        $found_class = $class;
        $function_name = $func_name;
        break;
      }
    }
    
    // If not found on the provided class, search through the pile (backward compatibility)
    if ($function_name === null) {
      [$function_name, $found_class] = $this->getValidFunctionName("get", $tag);
    }

    // Cache ReflectionMethod instance
    $reflection_key = (is_object($found_class) ? get_class($found_class) : $found_class) . '::' . $function_name;
    if (!isset($this->reflection_cache[$reflection_key])) {
      $this->reflection_cache[$reflection_key] = new ReflectionMethod($found_class, $function_name);
    }
    $rc = $this->reflection_cache[$reflection_key];
    
    $doc = $rc->getDocComment();
    // Match @return type, handling both single types and array types (with [])
    preg_match("/@return\s+([^\s\[\]]+)(\[\])?/", $doc, $matches);
    if (count($matches) > 1) {
      $type = $matches[1]; // Get the base type without []
      // Ensure we remove any [] that might have been captured separately
      $type = preg_replace("/\[\]/", "", $type);
      $type = trim($type);
    } else {
      $type = "\\DedexBundle\\Entity\\Ern{$this->version}\\{$tag}Type";
    }

    // Cache the result
    $this->type_cache[$cache_key] = $type;
    return $type;
  }

  /**
   * Instanciate an object with type guessed from doc. Use $value_default during
   * instanciation. Special case for DateTime.
   *
   * Based on documentation rather than parameters type because documentation
   * is more comprehensive (as generated by xsd2php)
   *
   * @param mixed $class
   * @param string $function
   * @param mixed $value_default
   * @return \DedexBundle\Controller\type
   */
  private function instanciateTypeFromDoc($class, $function, $value_default) {
    try {
      $rc = new ReflectionMethod($class, $function);
    } catch (\ReflectionException $e) {
      // If class doesn't have constructor, simply instantiate it
      if ($function === '__construct') {
          return new $class($value_default);
      }
      throw $e;
    }
    $doc = $rc->getDocComment();
    preg_match("/@param (\S+).*/", $doc, $matches);

    if (count($matches) === 0 || in_array($matches[1], ["string", "int", "bool", "float", "mixed"])) {
      return $value_default;
    }

    $type = $matches[1];
    // Remove [] from array types (e.g., "PartyIdType[]" -> "PartyIdType")
    $type = preg_replace("/\[\]/", "", $type);
    $type = trim($type);
    $this->log("create type $type");
    if ($type == "\DateTime") {
      // Use unified date parsing function
      $new_elem = $this->parseDateString($value_default);
      // If parsing returned null (empty/invalid value), skip setting the value
      if ($new_elem === null) {
        return null;
      }
      // DO NOT convert to UTC - preserve original timezone for DateTime objects
      // The tests expect the time as it appears in the XML, not converted to UTC
    } elseif ($type == "\DateInterval") {
        // Check for ISO8601:2004 format
        preg_match('/^P(?:(\d+D))?(T(?:(\d+H))?(?:(\d+M))?(?:(\d+(?:\.\d+)?S))?)?$/i', $value_default, $matches);
        if (!empty($matches)) {
            $new_elem = $this->intervalFromIso86012004String($value_default);
        } else {
            // Try to create DateInterval, but handle invalid values gracefully
            try {
                $new_elem = new DateInterval($value_default);
            } catch (\Exception $e) {
                // If the value is invalid (e.g., "Invalid date"), create a zero interval
                // This allows parsing to continue even with malformed duration values
                $new_elem = new DateInterval("PT0S");
            }
        }
    } elseif (is_subclass_of($type, EventDateTimeType::class)) {
        // EventDateTimeType expects a DateTime object
        // Parse the date string and create DateTime, then convert to UTC for consistent formatting
        $dt = $this->parseDateString($value_default);
        if ($dt === null) {
          return null;
        }
        // Convert to UTC for consistent formatting
        $dt->setTimezone(new \DateTimeZone('UTC'));
        $new_elem = new $type($dt);
        // Use reflection to set formatted string in __value (avoids entity class modifications)
        $this->setFormattedDateValue($new_elem, $dt, true); // true = date-time format
    } elseif ($this->isEventDateTimeWithoutFlagsType($type)) {
        // EventDateTimeWithoutFlagsType (used in ERN 4.3) expects a DateTime object
        // Parse the date string and create DateTime, then convert to UTC for consistent formatting
        $dt = $this->parseDateString($value_default);
        if ($dt === null) {
          return null;
        }
        // Convert to UTC for consistent formatting
        $dt->setTimezone(new \DateTimeZone('UTC'));
        $new_elem = new $type($dt);
        // Use reflection to set formatted string in __value (avoids entity class modifications)
        $this->setFormattedDateValue($new_elem, $dt, true); // true = date-time format
    } elseif ($type === '\\' . Ern341EventDateType::class) {
        // DdexC\EventDateType (ERN 341) expects a DateTime object
        $dt = $this->parseDateString($value_default);
        if ($dt === null) {
          return null;
        }
        // Convert to UTC for consistent formatting
        $dt->setTimezone(new \DateTimeZone('UTC'));
        $new_elem = new $type($dt);
        // Use reflection to set formatted string in __value (avoids entity class modifications)
        $this->setFormattedDateValue($new_elem, $dt, false); // false = date-only format
    } elseif (is_subclass_of($type, EventDateType::class)) {
        // Check if this is DdexC\EventDateType (expects DateTime)
        if ($type === 'DedexBundle\\Entity\\DdexC\\EventDateType') {
          // DdexC\EventDateType expects DateTime
          $dt = $this->parseDateString($value_default);
          if ($dt === null) {
            return null;
          }
          // Convert to UTC for consistent formatting
          $dt->setTimezone(new \DateTimeZone('UTC'));
          $new_elem = new $type($dt);
          // Use reflection to set formatted string in __value (avoids entity class modifications)
          $this->setFormattedDateValue($new_elem, $dt, false); // false = date-only format
        } else {
          // For all other EventDateType classes, they expect strings
          // EventDateType should remain date-only (YYYY-MM-DD) - don't add time component
          // Only normalize timezone if present (Z -> +00:00)
          $formatted = $value_default;
          if (substr($formatted, -1) === 'Z') {
            $formatted = substr($formatted, 0, -1) . '+00:00';
          }
          $new_elem = new $type($formatted);
        }
    } else {
      try {
        $new_elem = new $type($value_default);
      } catch (\TypeError $e) {
        // If constructor expects a non-scalar value, recursively check the expected type
        $value = $this->instanciateTypeFromDoc($type, '__construct', $value_default);
        $new_elem = new $type($value);
      }
    }
    return $new_elem;
  }

  /**
   * Centralized date formatting: formats date values based on the object type.
   * This ensures consistent date formatting across all ERN versions without
   * requiring modifications to each entity class.
   * 
   * IMPORTANT: 
   * - EventDateType (start_date/end_date) should remain date-only (YYYY-MM-DD)
   * - EventDateTimeType (start_time/end_time) should be date-time with timezone (YYYY-MM-DDThh:mm:ss+00:00)
   * 
   * @param object $obj The object receiving the date value
   * @param string $value Date string from XML
   * @return string Formatted date string
   */
  private function formatDateValueForObject($obj, string $value): string {
    $class_name = get_class($obj);
    
    // EventDateTimeType and EventDateTimeWithoutFlagsType: format as date-time with timezone
    if (is_subclass_of($class_name, EventDateTimeType::class) ||
        $this->isEventDateTimeWithoutFlagsType($class_name)) {
      // Format the date string to include time and timezone
      return $this->formatDateStringForEventDateType($value);
    }
    
    // EventDateType: keep as date-only (don't add time component)
    // The value should remain in YYYY-MM-DD format as per XSD specification
    if (is_subclass_of($class_name, EventDateType::class)) {
      // Only normalize timezone if present (Z -> +00:00), but don't add time
      if (substr($value, -1) === 'Z') {
        return substr($value, 0, -1) . '+00:00';
      }
      // Return as-is for date-only format
      return $value;
    }
    
    // Not a date type, return as-is
    return $value;
  }

  /**
   * Check if a class is EventDateTimeWithoutFlagsType (used in ERN 4.3)
   * 
   * @param string $class_name
   * @return bool
   */
  private function isEventDateTimeWithoutFlagsType(string $class_name): bool {
    // Check for EventDateTimeWithoutFlagsType in various ERN versions
    return strpos($class_name, 'EventDateTimeWithoutFlagsType') !== false;
  }

  /**
   * Use reflection to set formatted date string in __value property.
   * This allows us to avoid modifying entity classes - all formatting is done in the parser.
   * 
   * @param object $obj The entity object (EventDateType, EventDateTimeType, etc.)
   * @param \DateTime $dt The DateTime object to format
   * @param bool $isDateTimeType If true, format as date-time (Y-m-d\TH:i:s+00:00), if false format as date-only (Y-m-d)
   */
  private function setFormattedDateValue($obj, \DateTime $dt, bool $isDateTimeType): void {
    try {
      $reflection = new \ReflectionClass($obj);
      $property = $reflection->getProperty('__value');
      $property->setAccessible(true);
      
      // Format based on type
      if ($isDateTimeType) {
        // EventDateTimeType: format as date-time with timezone
        $formatted = $dt->format('Y-m-d\TH:i:s') . '+00:00';
      } else {
        // EventDateType: format as date-only
        $formatted = $dt->format('Y-m-d');
      }
      
      $property->setValue($obj, $formatted);
    } catch (\ReflectionException $e) {
      // If reflection fails, log but don't break parsing
      $this->log("Warning: Could not set formatted date value via reflection: " . $e->getMessage());
    }
  }

  /**
   * Format a date string for EventDateType (string-based).
   * Ensures date-only formats get time component and all dates have timezone.
   * 
   * @param string $value Date string from XML
   * @return string Formatted date string (Y-m-d\TH:i:s+00:00 format)
   */
  private function formatDateStringForEventDateType(string $value): string {
    $value = trim($value);
    if (empty($value)) {
      return $value;
    }
    
    // Remove milliseconds if any
    $value = preg_replace("/\.\d+\+/", "+", $value);
    $value = preg_replace("/\.\d+Z/", "Z", $value);
    
    // Date-only format: add time and timezone
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
      return $value . 'T00:00:00+00:00';
    }
    
    // Date-time without timezone: add UTC timezone
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $value)) {
      return $value . '+00:00';
    }
    
    // Date-time with Z: convert to +00:00
    if (substr($value, -1) === 'Z') {
      return substr($value, 0, -1) . '+00:00';
    }
    
    // Already has timezone: return as-is (should already be in correct format)
    return $value;
  }

  /**
   * Parse a date string into a DateTime object.
   * If time is missing, defaults to 00:00:00 UTC.
   * Preserves original timezone if present, otherwise defaults to UTC.
   * 
   * @param string $value Date string (can be date-only or date-time)
   * @return DateTime|null Returns DateTime object or null if value is empty
   * @throws Exception If date cannot be parsed
   */
  private function parseDateString(string $value): ?DateTime {
    $original_value = $value;
    $value = trim($value);
    
    // Empty values return null
    if (empty($value)) {
      return null;
    }
    
    $is_date_only = false;
    $has_timezone = false;
    $timezone = null;
    
    // Remove milliseconds if any (e.g., "2024-01-15T10:30:00.123+00:00" -> "2024-01-15T10:30:00+00:00")
    $value = preg_replace("/\.\d+\+/", "+", $value);
    $value = preg_replace("/\.\d+Z/", "Z", $value);
    
    // Check if this is a date-only format (YYYY-MM-DD) without time
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
      // Date-only format: default to 00:00:00 UTC
      $value .= 'T00:00:00+00:00';
      $is_date_only = true;
      $timezone = new \DateTimeZone('UTC');
      $this->log("Date-only format detected, defaulting to 00:00:00 UTC: " . $value);
    }
    // Check if this is a date with time but no timezone (YYYY-MM-DDTHH:MM:SS)
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $value)) {
      // Date-time without timezone: default to UTC
      $value .= '+00:00';
      $timezone = new \DateTimeZone('UTC');
      $this->log("Date-time without timezone detected, defaulting to UTC: " . $value);
    }
    // Check if this is a date with time and milliseconds but no timezone (YYYY-MM-DDTHH:MM:SS.mmm)
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+$/', $value)) {
      // Date-time with milliseconds but no timezone: default to UTC
      $value .= '+00:00';
      $timezone = new \DateTimeZone('UTC');
      $this->log("Date-time with milliseconds but no timezone detected, defaulting to UTC: " . $value);
    }
    // Handle UTC timezone indicator (Z) - convert to +00:00 for consistent formatting
    elseif (substr($value, -1) === 'Z') {
      $has_timezone = true;
      // Convert Z to +00:00 for consistent formatting
      $value = substr($value, 0, -1) . '+00:00';
    }
    // Check if it has a timezone offset (e.g., +04:00, -05:00)
    elseif (preg_match('/[+-]\d{2}:\d{2}$/', $value)) {
      $has_timezone = true;
      // Extract timezone from the value
      if (preg_match('/([+-]\d{2}):(\d{2})$/', $value, $tz_matches)) {
        $tz_offset = $tz_matches[1] . $tz_matches[2]; // e.g., "+0400"
        try {
          $timezone = timezone_open(sprintf('Etc/GMT%s', str_replace(['+', '-'], ['-', '+'], $tz_offset)));
        } catch (\Exception $e) {
          // Fallback to UTC if timezone parsing fails
          $timezone = new \DateTimeZone('UTC');
        }
      }
    }
    
    // Use DateTime constructor which handles timezones correctly
    // It will preserve the original timezone if present, or use the default timezone
    try {
      // Create DateTime - it will parse the timezone from the string if present
      $new_elem = new DateTime($value);
      
      // If this was originally a date-only format, ensure time is 00:00:00
      if ($is_date_only) {
        $new_elem->setTime(0, 0, 0);
        // For date-only, explicitly set to UTC
        $new_elem->setTimezone(new \DateTimeZone('UTC'));
      } elseif (!$has_timezone && $timezone !== null) {
        // If we added a timezone (date-time without timezone), set it
        $new_elem->setTimezone($timezone);
      }
      // DO NOT convert to UTC here - preserve original timezone
      // DateTime objects (like message_date) should keep their original timezone
      // EventDateType/EventDateTimeType will convert to UTC when needed
      
      return $new_elem;
    } catch (\Exception $e) {
      // DateTime constructor failed - value is likely invalid
      $fileInfo = $this->file_path ? " File: {$this->file_path}" : "";
      throw new Exception("Failed to parse date value: '{$original_value}'" . $fileInfo, 0, $e);
    }
  }

  protected function intervalFromIso86012004String(string $value): DateInterval
  {
      preg_match('/PT([0-9.,]*H)?([0-9.,]*M)?([0-9.,]*S)?/i', $value, $matches);

      $hours = $matches[1] ?? null;
      $minutes = $matches[2] ?? null;
      $seconds = $matches[3] ?? null;

      $hours = (!empty($hours)) ? str_replace('H', '', $hours) : 0;
      $minutes = (!empty($minutes)) ? str_replace('M', '', $minutes) : 0;
      $seconds = (!empty($seconds)) ? str_replace('S', '', $seconds) : 0;

      $split = explode('.', $seconds);
      $seconds = $split[0];
      $fraction = $split[1] ?? null;

      $now = new DateTime();
      $next = clone $now;

      $next->add(DateInterval::createFromDateString(sprintf('%s hours', $hours)));
      $next->add(DateInterval::createFromDateString(sprintf('%s minutes', $minutes)));
      $next->add(DateInterval::createFromDateString(sprintf('%s seconds', $seconds)));
      if (!empty($fraction)) {
          $next->add(DateInterval::createFromDateString(sprintf('%s microseconds', $fraction)));
      }

      return $now->diff($next);
  }

  /**
   * Function called when data is encountered in the XML file
   *
   * @param type $parser
   * @param string $data
   */
  private function callbackCharacterData($parser, string $data) {
    if (trim($data) === "") {
      // do nothing
      return;
    }

    if ($this->ignored_element_depth > 0) {
      return;
    }

    // Special handling for nested <Extent> elements
    if ($this->handling_nested_extent) {
      // Accumulate value (XML parser may split values across multiple calls)
      $this->nested_extent_value .= $data;
      return;
    }

    $this->setCurrentElement($data);
  }

  /**
   * Creates the parser and affect the callback functions
   *
   * @param string $file
   * @return boolean
   */
  private function newXmlParser(string $file) {
    // Create parser
    $this->xml_parser = xml_parser_create();

    // Activate case folding
    xml_parser_set_option($this->xml_parser, XML_OPTION_CASE_FOLDING, 0);

    // Set callback functions
    xml_set_element_handler($this->xml_parser, [$this, "callbackStartElement"], [$this, "callbackEndElement"]);
    xml_set_character_data_handler($this->xml_parser, [$this, "callbackCharacterData"]);
    // xml_set_external_entity_ref_handler($this->xml_parser, "externalEntityRefHandler");
    // Open file
    if (!($fp = @fopen($file, "r"))) {
      return false;
    }

    return array($this->xml_parser, $fp);
  }

  /**
   * Look for text xmlns:ern="http://ddex.net/xml/ern/382" or 41 in XML file
   * Also handles ERN-Main 32: xmlns:ern="http://ddex.net/xml/2010/ern-main/32"
   * @param filedescription $fp Open wml file. Considered as valid XML file at this point
   * @return string version such as "382" or "41" or "32"
   * @throws Exception
   */
  private function detectVersion($fp) {
    $version = null;
    $supported_versions = [
        "43",
        "411",
        "41",
        "382",
        "381",
        "383",
        "341",
        "371",
        "37",
        "32",
    ];

    while (($buffer = fgets($fp, 4096)) !== false) {
      $trimed = trim($buffer);
      if (empty($trimed)) {
        continue;
      }

      // Try to find ERN-Main 32 namespace first (http://ddex.net/xml/2010/ern-main/32)
      $re_ern_main = '/xmlns:ernm?="https?:\/\/ddex.net\/xml\/2010\/ern-main\/(\d+)"/m';
      preg_match_all($re_ern_main, $trimed, $matches_ern_main, PREG_SET_ORDER, 0);
      
      if (!empty($matches_ern_main)) {
        $version = $matches_ern_main[0][1];
        break;
      }

      // Try to find version in this line (standard ERN pattern)
      $re = '/xmlns:ernm?="https?:\/\/ddex.net\/xml\/ern\/(\d+)"/m';
      preg_match_all($re, $trimed, $matches, PREG_SET_ORDER, 0);

      if (empty($matches)) {
        continue;
      }

      $version = $matches[0][1];
      break;
    }

    if ($version === null) {
      throw new XmlLoadException("Could not find the xmlns:ern in the beginning of this file.");
    }

    if (!in_array($version, $supported_versions)) {
      throw new VersionNotSupportedException(sprintf("Version found in this XML is %s. Support only versions %s.", $version, implode(",", $supported_versions)));
    }

    rewind($fp);
    return $version;
  }

  /**
   * Validates XML against XSD (according to version).
   * Will load both files in memory.
   *
   * @param string $file_path
   * @throws XsdCompliantException
   */
  private function validateXml(string $file_path) {
    if (!$this->xsd_validation) {
      return;
    }

    $xml_reader = new XMLReader();
    $xml_reader->open($file_path);

    $xsd_file_path = __DIR__ . "/../../xsd/release_notification/{$this->version}/release-notification.xsd";
    $xml_reader->setSchema($xsd_file_path);

    try {
      while ($xml_reader->read()) {
        continue;
      }
    } catch (\Exception $ex) {
      throw new XsdCompliantException("This XML file $file_path does not validates XSD $xsd_file_path. Error: {$ex->getMessage()}");
    }
  }

  /**
   * Display all warning messages then error messages, one per line.
   *
   * @return type
   */
  private function getInvalidatedRuleMessages() {
    $message = "";

    foreach (array_merge($this->rule_messages[Rule::LEVEL_WARNING], $this->rule_messages[Rule::LEVEL_ERROR]) as $msg) {
      $message .= "\n- ".$msg;
    }

    return $message;
  }

  /**
   * Check the new release message validates all rules.
   * Will raise an exception and display all messages if one error pops up.
   *
   * @throws RuleValidationException
   */
  private function validateRules() {
    foreach ($this->rules as $rule) {
      if (!in_array($this->version, $rule->getSupportedVersions())) {
        continue;
      }

      $valid = $rule->validates($this->ern);
      if (!$valid) {
        $this->rule_messages[$rule->getLevel()][] = $rule->getMessage();
      }
    }

    if (count($this->rule_messages[Rule::LEVEL_ERROR]) > 0) {
      throw new RuleValidationException("Some rules with level ERROR did not pass.\n"
              . $this->getInvalidatedRuleMessages()
      );
    }
  }

  /**
   * Return all messages generated by rules.
   * @return string
   */
  public function getRuleMessages() {
    return $this->getInvalidatedRuleMessages();
  }

  /**
   * This is the main parsing function that will go through the whole XML
   *
   * @param string $file_path Location of XML path
   * @return Ddex The main entity modelling the full DDex file
   */
  public function parse(string $file_path) {
    // Clear all caches at start of each parse
    $this->method_exists_cache = [];
    $this->reflection_cache = [];
    $this->namespace_cache = [];
    $this->type_cache = [];
    $this->function_names_cache = [];
    $this->ignored_element_depth = 0;

    $this->file_path = $file_path;

    if (!file_exists($file_path)) {
      throw new FileNotFoundException("File not found: $file_path");
    }

    // Create parser, will be stored in $this->xml_parser
    if (!(list($this->xml_parser, $fp) = $this->newXmlParser($file_path))) {
      throw new XmlLoadException("Can't load XML file: $file_path");
    }

    $this->version = $this->detectVersion($fp); // 382 or 41
    // Validate xml against XSD
    $this->validateXml($file_path);

    // Parse XML now
    while ($data = fread($fp, 4096)) {
      try {
        if (!xml_parse($this->xml_parser, $data, feof($fp))) {
          throw new XmlParseException(sprintf("XML Error: %s at line %d\n",
                          xml_error_string(xml_get_error_code($this->xml_parser)),
                          xml_get_current_line_number($this->xml_parser)));
        }
      } catch (\Error $er) {
        throw new XmlParseException(sprintf("XML Error: %s at line %d\n",
                          $er->getMessage(),
                          $er->getLine()));
      }
    }

    // Free parser memory
    xml_parser_free($this->xml_parser);

    // Check for rules
    $this->validateRules();

    return $this->ern;
  }

}
